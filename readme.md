# Low Search Table for ExpressionEngine

This add-on allows you to filter entries by group calculations of Grid / Matrix columns, like lowest, highest, average, sum, and total rows with [Low Search](http://gotolow.com/addons/low-search). For example, filter by entries that have a Grid field with x rows or more. This also allows you to order entries by that value. *Requires Low Search 4.2+*.

## Installation

- Download and unzip;
- Copy the `low_search_table` folder to your `system/expressionengine/third_party` directory;
- All set!

## Usage

Once installed, you can use these parameters in this format:

    table:field_name:column_name:calculation="value"

`field_name` should correspond to the Grid / Matrix field. `column_name` should be a valid column within that field.Valid calculations are:

- `min`: lowest value;
- `max`: highest value;
- `avg`: average value;
- `sum`: sum of all values;
- `count`: total rows in field. Does not require a column name to be specified.

*Values* should be *numeric* and can be prepended with the following operators:

- `>`
- `>=`
- `<`
- `>=`
- `not`

To retrieve the value associated with the entry, use a variable like this:

    {low_search_table:field_name:column_name:calculation:val}

To order by a given value, use the parameter name, eg.

    orderby="table:field_name:column_name:calculation"

## Examples

### Get entries with a Grid field with exactly X rows

    <select name="table:grid_field:count">
        <option value="1">One</option>
        <option value="2">Two</option>
        <option value="3">Three</option>
    </select>

### Get entries where the age column of a Matrix field doesn't exceed 50

    <input name="table:matrix_field:age:max" value="<50">