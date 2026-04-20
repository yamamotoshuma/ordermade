# Batting Input UI

## Goal

- Improve in-game smartphone input speed on the batting create/edit screens.
- Keep the stored schema unchanged: `resultId1`, `resultId2`, `resultId3`.
- Avoid changes to batting list/detail HTML that other tools scrape.

## Current Approach

- `resources/views/batting/create.blade.php`
- `resources/views/batting/edit.blade.php`
- `resources/views/batting/partials/result-selector.blade.php`

The `かんたん入力` picker is only a front-end layer over the existing form fields:

- `resultId1`: batting result
- `resultId2`: direction or strike sub-type
- `resultId3`: RBI

The classic `select` inputs remain available under the `通常入力` tab.
The `かんたん入力` tab updates the same fields, so the controller and database behavior stay unchanged.

## UX Notes

- The top `試合・打者・イニング` block is collapsible so in-game mobile use starts with a shorter screen.
- When a registered batter is selected, the manual batter-name field is hidden.
- The create screen now defaults the batter to the next batting-order entry after the latest saved plate appearance when no explicit batter is supplied.
- The create screen now defaults the inning to the next inning when the latest inning already has 3 or more outs recorded.
- The inning field shows the current out count for the selected inning and asks for confirmation before submitting into an inning that already has 3 or more outs.
- `四球` is promoted into the frequently used result buttons.
- The field map is rendered with a fixed SVG viewBox so the infield dirt shape stays stable across phone sizes.
- Field-direction buttons were enlarged with responsive touch targets so outfield taps are easier on smartphones.

## Special Cases

- `四球` and `死球` automatically map `resultId2` to the blank direction master.
- `三振` shows `空振 / 見逃` instead of the field map.
- `振逃` automatically maps `resultId2` to `空振`.

If the direction/result rules need to become stricter later, change only the front-end mapping in the partial unless the data model itself is being redesigned.
