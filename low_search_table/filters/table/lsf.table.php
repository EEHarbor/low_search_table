<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Filter by min:grid:col="val", max:grid:col="val", avg:grid:col="val", sum:grid:col="val"
 *
 * @package        low_search
 * @author         Lodewijk Schutte ~ Low <hi@gotolow.com>
 * @link           https://github.com/low/low_search_members
 * @copyright      Copyright (c) 2015, Low
 */
class Low_search_filter_table extends Low_search_filter {

	/**
	 * Prefixes
	 */
	private $_pfx = 'table:';

	/**
	 * Allowed groups:
	 */
	private $_groups = array(
		'min', 'max', 'avg', 'sum', 'count'
	);

	/**
	 * Fixed order?
	 */
	private $_fixed;

	/**
	 * Results
	 */
	private $_results;

	// --------------------------------------------------------------------

	/**
	 * Allows for member:key="val" parameters
	 *
	 * @access     private
	 * @return     void
	 */
	public function filter($entry_ids)
	{
		// --------------------------------------
		// Reset
		// --------------------------------------

		$this->_fixed = FALSE;
		$this->_results = array();

		// --------------------------------------
		// Get the params
		// --------------------------------------

		$params = array_filter($this->params->get_prefixed($this->_pfx), 'low_not_empty');

		// --------------------------------------
		// Bail out of nothing's there
		// --------------------------------------

		if (empty($params))
		{
			return $entry_ids;
		}

		// --------------------------------------
		// Log it
		// --------------------------------------

		$this->_log('Applying '.__CLASS__);

		// --------------------------------------
		// loop through params and fire a query per param
		// --------------------------------------

		foreach ($params as $key => $val)
		{
			// Get parts
			$parts = explode(':', $key);

			// Get rid of the first one
			array_shift($parts);

			// How many parts do we have?
			$total_parts = count($parts);

			// Too little or too many parts
			if ($total_parts <= 1 || $total_parts >= 4) continue;

			// Get the last one
			$group = array_pop($parts);

			// Skip invalid group
			if ( ! in_array($group, $this->_groups)) continue;

			// If there's only one part left, add 'entry_id' to it
			if (count($parts) == 1)
			{
				$parts[] = 'entry_id';
			}

			// Then list them out
			list($field, $col) = $parts;

			// Now we have a field name, get its ID
			if ( ! ($field_id = $this->fields->id($field))) continue;

			// Set type and table based on fieldtype
			if ($this->fields->is_grid($field))
			{
				$table = 'channel_grid_field_'.$field_id;
				$type  = 'grid';
			}
			// Or a matrix field?
			elseif ($this->fields->is_matrix($field))
			{
				$table = 'matrix_data';
				$type  = 'matrix';
			}
			// or neither? Skip it then.
			else continue;

			// Let's turn our attention to the targeted column
			if ($col == 'entry_id')
			{
				$column = $col;
			}
			else
			{
				// Get grid or matrix column ID
				$method = $type . '_col_id';
				$col_id = $this->fields->$method($field_id, $col);

				// Skip invalid col IDs
				if ( ! $col_id) continue;

				// There it is
				$column = 'col_id_'.$col_id;
			}

			// Still here? Good. Next, let's look at the value. Prep it first.
			$val = $this->params->prep($key, $val);

			// Should be numeric, with optional operators
			if ( ! preg_match('/^(not|[<>]=?)?\s?([\d\.]+)$/', $val, $m)) continue;

			// Smooth operator
			switch($m[1])
			{
				case '>':
				case '>=':
				case '<':
				case '>=':
					$oper = ' '.$m[1];
				break;

				case 'not':
					$oper = ' !=';
				break;

				default:
					$oper = '';
			}

			// Numeric value
			$val = $m[2];

			// OK! Start query
			ee()->db
				->select('entry_id')
				->select("{$group}(`{$column}`) AS val")
				->from($table)
				->group_by('entry_id')
				->having('val'.$oper, $val);

			// Limit to field id for Matrix
			if ($type == 'matrix')
			{
				ee()->db->where('field_id', $field_id);
			}

			// Limit to entry IDs already given
			if ($entry_ids)
			{
				ee()->db->where_in('entry_id', $entry_ids);
			}

			// Are we ordering by anything?
			if ( ! $this->_fixed && $this->params->get('orderby') == $key)
			{
				// Then order and sort it
				ee()->db->order_by('val', $this->params->get('sort'));

				// Beware of the double flip
				$this->params->set('sort', 'asc');

				// Pass it on
				$this->_fixed = TRUE;
			}

			// Execute query and get results
			$rows = ee()->db->get()->result_array();

			// Remember results for later, grouped by entry id
			foreach ($rows as $row)
			{
				$var = ee()->low_search_settings->prefix . $key . ':val';
				$this->_results[$row['entry_id']][$var] = $row['val'];
			}

			// These are the entry IDs we have left
			$entry_ids = low_flatten_results($rows, 'entry_id');

			// If there are none, bail
			if (empty($entry_ids)) break;
		}

		// --------------------------------------
		// Return it
		// --------------------------------------

		return $entry_ids;
	}

	/**
	 * Fixed order?
	 */
	public function fixed_order()
	{
		return $this->_fixed;
	}

	/**
	 * Add vars to results
	 */
	public function results($rows)
	{
		foreach ($rows as &$row)
		{
			if (array_key_exists($row['entry_id'], $this->_results))
			{
				$row = array_merge($row, $this->_results[$row['entry_id']]);
			}
		}

		return $rows;
	}
}