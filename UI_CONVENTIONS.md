# Pallet UI conventions

These rules are **derived from the reference modules**, not invented. Every one cites
the file it came from, so a disagreement can be settled by reading that file rather
than by argument. FleetOps is the most developed engine and is the primary reference;
ember-ui is the component library both consume.

Paths below are relative to `packages/fleetops` or to the installed
`@fleetbase/ember-ui`.

---

## 1. Never hand-roll what ember-ui provides

Pallet has repeatedly built its own version of a component that already exists.
Before writing markup, check `@fleetbase/ember-ui/addon/components`.

| Need                    | Use                                                                  | Do not                                  |
| ----------------------- | -------------------------------------------------------------------- | --------------------------------------- |
| A list screen           | `Layout::Resource::Tabular`                                          | `ContentPanel` wrapping a raw `<table>` |
| Primary identity column | `Table::Cell::ResourceIdentity`                                      | a bespoke `*-cell` component            |
| An ID column            | `click-to-copy` — the most-used cell in FleetOps (33 uses)           | plain text                              |
| Status                  | `Table::Cell::Status` / `Badge`                                      | a hand-styled span                      |
| Row actions             | `Table::Cell::Dropdown`                                              | inline buttons per row                  |
| A numeric column        | `cell/count` (Pallet-local; ember-ui's base cell prints `-` for `0`) | `table/cell/base`                       |
| An enumeration input    | `Select` with `@humanize={{true}}`                                   | a free-text `Input`                     |

`Table::Cell::ResourceIdentity` accepts `labelPath`, `mediaUrl`, `status`, `size` and
renders the avatar, label and status dot for you — see
`addon/components/cell/vehicle-identity.hbs`, which delegates to it.

---

## 2. Density

Fleetbase is compact. Measured across FleetOps' `details.hbs` and `form.hbs`:
`gap-2` (184 uses), `text-xs` (166), `text-[11px]` (104), `py-2`/`px-2`/`px-3`.

- **Table cells**: `px-2 py-2` or `px-3 py-2`. **Not** `px-4 py-3` — Pallet's old
  hand-rolled tables used that 74 times and rows came out roughly 1.5× too tall.
- **Field/grid gap**: `gap-2`.
- **Body text**: `text-xs`. Identity labels `text-sm leading-4`.
- **Identity avatars in cells**: `h-5 w-5` (`cell/vehicle-identity.hbs`).
- **Secondary badges in cells**: `text-[10px] leading-4`.
- Long cell text **truncates** (`min-w-0 truncate`) rather than wrapping to a second
  line and doubling row height.

---

## 3. The 11px label tier

This is the single most visible thing Pallet was missing. FleetOps groups fields
under a small uppercase heading spanning the grid:

```hbs
<div class='col-span-2 text-[11px] uppercase tracking-wide text-gray-500 font-semibold mt-1'>
    Vehicle Identification
</div>
```

**But do not copy that recipe verbatim.** ember-ui already sets `.field-name` to
`text-[11px] tracking-wide uppercase font-semibold` (`addon/styles/layout/next.css`),
so a heading styled the same way is pixel-identical to the labels beneath it and
the grouping reads as noise. Measured in the console: heading and label came back
identical on every property. Use `pallet-field-group-heading` — heavier, darker,
ruled off — which is the tier FleetOps' recipe was reaching for.

For the fields themselves prefer `field-info-container field-vertical-container
dashed-bottom`: label left, value right, one line each. That is what FleetOps'
`order/details/detail.hbs` uses, and it halves the height of a stacked list.

A panel with more than about six fields should be grouped. An ungrouped wall of
label/value pairs is what makes a screen read as unfinished.

---

## 4. Progressive disclosure

FleetOps opens the **first** panel and collapses the rest:

```hbs
<ContentPanel @title={{t "common.details"}} @open={{true}}  @wrapperClass="bordered-top">
<ContentPanel @title="Technical Specifications" @open={{false}} @wrapperClass="bordered-top">
```

`vehicle/details.hbs` has seven panels and opens two. Pallet opened every panel on
every screen, which is why its detail views scroll forever. Open what a reader needs
first; collapse the rest.

---

## 5. Empty values

Prefer hiding an optional field to printing a placeholder:

```hbs
{{#if @resource.default_assignee.name}}
    <div class='field-info-container'>…</div>
{{/if}}
```

Use `n-a` where a dash genuinely communicates "none recorded" — a column that must
keep its shape across rows. Do **not** stack placeholders (`Untitled product`,
`SKU not set`, `No description`, `Uncategorized`, `No supplier`) — five reassuring
strings make a broken record look merely incomplete, and they triple row height.

`n-a` takes its **second argument as the fallback**. `{{n-a a b}}` means "a, else b" —
if `b` is itself nullable the result is empty, not a dash. Write `{{n-a (or a b)}}`.

---

## 6. Structure of a form

From `vehicle/form.hbs`:

```hbs
<div class='form-wrapper' ...attributes>
    <ContentPanel @title={{t 'common.details'}} @open={{true}} @wrapperClass='bordered-top'>
        <div class='grid grid-cols-1 gap-2 lg:grid-cols-2 lg:gap-2 no-input-group-padding text-xs'>
            <div class='col-span-1 lg:col-span-2 text-[11px] uppercase tracking-wide text-gray-500 font-semibold mt-1'>
                Group Name
            </div>
            <InputGroup @name={{t 'common.name'}}>
                <Input @value={{@resource.name}} @type='text' class='w-full form-input' placeholder={{t 'common.name'}} disabled={{cannot-write @resource}} />
            </InputGroup>
        </div>
    </ContentPanel>
</div>
```

Every input carries `disabled={{cannot-write @resource}}`. The grid is responsive
(`grid-cols-1 … lg:grid-cols-2`), never a fixed `grid-cols-2`.

---

## 7. Component APIs that have already caught us

Check the component's own source before assuming an argument name.

- `Checkbox` reads `@value` and `@label`. It does **not** read `@checked`.
- `notifications.serverError(e)` reads `errors` off the object it is handed. Passing
  `{ payload: { errors: [...] } }` silently yields the generic fallback message.
- It renders `errors[0]` only — put the detail in that one string.
- `@belongsTo` defaults to **async**, so the relation is a proxy that stays truthy
  when its content is null. Read through Ember's `get`.
- A method called from a template as `(this.thing arg)` must be `@action`, or it
  arrives unbound and `this` is undefined.
- A `{{! }}` comment ends at its first `}}`. If the comment quotes a helper, use
  `{{!-- --}}`.
- `Overlay::Header` renders its title, status badge and createdAt **only when it
  has no default block**. Buttons go in the `actions` named block; putting them
  in the default block replaces the whole left section and the title vanishes.
- `MoneyInput` does not read `@disabled`. Its inner `<Input>` takes
  `...attributes`, so write `disabled={{cannot-write @resource}}` — no `@`.
- `Select` reads `@disabled` **once, in its constructor**, and never updates it.
- `Layout::Resource::Panel` reads `@authSchema`, not `@authScheme`.
- `Layout::Resource::Tabular` has no `@canCreate`, `@canDelete`,
  `@searchPlaceholder` or `@resource`. The New button renders only when
  `@onPressNew` is passed — omitting it is how you hide it.
- Also silently ignored where Pallet passes them: `CountryName @showFlag`,
  `FileUpload @for` / `@multiple`, `CoordinatesInput @onInit`,
  `PhoneInput @autocomplete`.

**Blocks are as easy to get wrong as arguments, and fail just as silently.** Audit
both. Three module-wide defects came from blocks alone:

- `Overlay::Header` renders its left section — title, status, createdAt — only
  when it has **no default block**. Buttons belong in `<:actions>`.
- `Layout::Resource::Tabular` has no `subheader` block. Its only block *replaces
  the table*. Anything else goes above the component.
- `Layout::Section::Header`'s only `{{yield}}` sits inside the actions wormhole,
  so a block passed to it renders where the buttons go, not under the title. Use
  `@subtitle`, or leave it out — FleetOps resource lists carry no description.

Every entry above came from reading the component's own source. An argument the
component never reads is worse than no argument: it reads as a working control.

---

## 8. Styling

Pallet carries ~1500 lines of `pallet-*` CSS. Do not add to it where an ember-ui
primitive or a utility class will do. Namespaced classes are acceptable for genuinely
novel surfaces; they are not acceptable as a parallel implementation of something the
design system already ships.

Never redefine an ember-ui class name globally.
