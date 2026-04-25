# Batting Input UI

## Goal

- Improve in-game smartphone input speed on the batting create/edit screens.
- Keep the core batting result schema centered on `resultId1`, `resultId2`, `resultId3`.
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

Batting-around support now adds `batting_stats.inningTurn` so the same batter can have a second or third plate appearance in the same inning without overwriting the first one.

## UX Notes

- The create screen now keeps batter and inning controls visible at all times. The game name is intentionally small because it usually does not change during entry.
- Inning can be changed with direct input or `− / ＋` buttons so manual correction is quick when non-batting outs or scorekeeping drift make the automatic default wrong.
- The batter selector is one batting-order-based dropdown. It hides the internal distinction between `userId` players and order-only `userName` players, then writes the correct hidden value for persistence.
- The edit screen follows the same batter/inning UI as create: the same unified batter dropdown, visible inning correction controls, and compact game display.
- The create screen now defaults the batter to the next batting-order entry after the latest saved plate appearance when no explicit batter is supplied.
- The create screen now defaults the inning to the next inning when the latest inning already has 3 or more outs recorded.
- The inning field shows the current out count for the selected inning and asks for confirmation before submitting into an inning that already has 3 or more outs.
- The create screen warns when the live base state strongly suggests at least one RBI but the entered RBI is too low. This warning only applies when the selected inning/batter still matches the current live at-bat so delayed back-entry does not produce noisy alerts.
- If the same batter is selected again in the same inning before every batting-order slot has at least one result for that inning, the UI keeps a warning step to reduce accidental duplicate entry.
- If the inning has already gone through the full batting order, the second plate appearance is added automatically as `inningTurn = 2` without the extra warning.
- The create screen shows a fixed bottom submit bar so the user does not need to scroll to the end of the form during a game.
- The fixed submit bar now emphasizes the current inning, out count, next batter, and the exact result/RBI that will be registered.
- After a successful create/update from the create screen, a `直前の入力` card appears with quick links to edit, undo/delete, or continue entering the next plate appearance.
- The edit screen also uses a fixed bottom action bar so `更新する` and `削除` stay adjacent and easy to reach without a long scroll.
- When the user opens edit from the `直前の入力` card, update/delete returns to the create screen because that flow is considered an in-game quick correction.
- Create/update flows that show the `直前の入力` card do not also show a generic success message; the card itself is the completion feedback.
- Result/direction values are intentionally not restored from localStorage on the create screen after a successful entry. The next batter and inning continue automatically, while the result/direction reset to blank and RBI defaults to `0` to reduce accidental repeat entries.
- Result buttons are grouped and color-coded by outcome type (`出塁`, `長打`, `アウト`), but only one group is expanded at a time to keep the create screen short on phones.
- In `かんたん入力`, selected result and direction collapse into compact summary rows with `変更` buttons. This keeps the current step close to the top and avoids a long result-to-field scroll on phones.
- The field map keeps the same `10 / 9` aspect ratio as its SVG `viewBox` so the green field, button positions, and infield shape do not drift across device sizes. Do not compress it with a different CSS aspect ratio; reduce scroll by collapsing completed steps instead.
- `四球` is promoted into the frequently used result buttons.
- The field map is rendered with a fixed SVG viewBox so the infield dirt shape stays stable across phone sizes.
- Field-direction buttons were enlarged with responsive touch targets so outfield taps are easier on smartphones.

## Runner State UX

- The create screen now includes a compact `現在の攻撃状況` card above the result selector.
- The card shows inning, out count, next batter, and a small diamond with the current runners.
- `走者操作` opens a bottom sheet so the user can keep working on the same screen without leaving batting input.
- Common in-game actions are one-tap buttons per base: `盗塁`, `重盗`, `進塁`, `盗塁死`, `牽制死`, `走塁死`, `ベースを空にする`.
- If the next base is already occupied, the steal button switches from `盗塁` to `重盗`. A `重盗` operation records stolen bases for every forced runner in that chain, then undo removes the whole chain as one operation.
- `手動配置` exists as a correction tool for real-game drift such as delayed scoring,牽制アウト, or manual scorebook fixes.
- After batting results that cannot fully determine all runner movement from the existing schema alone, the offense-state cache flags `needsRunnerConfirmation` and auto-opens the runner sheet.
- The runner-state cache uses `base_running_events` plus `batting_stats`, but the game detail batting HTML is left intact. Existing display pages should keep the same structure while create-screen defaults become more accurate.

## Batting Table Layout

- `game.show` and `batting.index` now render batting-around by adding a blank-header column to the right of the inning where a second plate appearance exists.
- Example: if inning 3 has a second plate appearance, the header sequence becomes `1 | 2 | 3 | (blank) | 4 ...`.
- Each cell still represents a single saved plate appearance, so edit links remain one-to-one with `batting/{id}`.

## Special Cases

- `四球` and `死球` automatically map `resultId2` to the blank direction master.
- `三振` shows `空振 / 見逃` instead of the field map.
- `振逃` automatically maps `resultId2` to `空振`.

If the direction/result rules need to become stricter later, change only the front-end mapping in the partial unless the data model itself is being redesigned.
