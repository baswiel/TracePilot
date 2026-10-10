---
name: TracePilot
description: A calm operational workspace for incident triage.
colors:
  "primary": "#1267f4"
  "primary-dark": "#79acff"
  "foreground": "#101d3f"
  "background": "#f6f8fc"
  "surface": "#ffffff"
  "border": "#dfe5ee"
  "muted-foreground": "#64728b"
  "secondary": "#edf3ff"
  "muted": "#f1f4f9"
  "accent": "#eaf2ff"
  "danger": "#ef3340"
  "warning": "#f58a07"
  "success": "#10945a"
  "danger-ink": "#c82333"
  "danger-soft": "#fff1f2"
  "danger-border": "#fecdd3"
  "warning-ink": "#a45505"
  "warning-soft": "#fff8eb"
  "warning-border": "#f4d49a"
  "success-ink": "#087449"
  "success-soft": "#ecfdf5"
  "success-border": "#a7e5cc"
  "background-dark": "#0d1731"
  "surface-dark": "#142142"
  "muted-dark": "#1b2b51"
  "muted-foreground-dark": "#b8c3d7"
  "accent-dark": "#1b3262"
  "accent-foreground-dark": "#dce9ff"
  "border-dark": "#2a3b61"
  "danger-ink-dark": "#fda4af"
  "danger-soft-dark": "#362338"
  "danger-border-dark": "#643046"
  "warning-ink-dark": "#f8c477"
  "warning-soft-dark": "#332b28"
  "warning-border-dark": "#66503a"
  "success-ink-dark": "#6ee7b7"
  "success-soft-dark": "#153c37"
  "success-border-dark": "#276450"
typography:
  headline:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "clamp(1.875rem, 3vw, 2.5rem)"
    fontWeight: 600
    letterSpacing: "-0.035em"
  page-title:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.5rem"
    fontWeight: 600
    lineHeight: "2rem"
    letterSpacing: "-0.025em"
  section-title:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.125rem"
    fontWeight: 600
    lineHeight: "1.75rem"
  body:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: "1.25rem"
  label:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 500
    lineHeight: "1.25rem"
  button-label:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 600
    lineHeight: "1.25rem"
  settings-heading:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.25rem"
    fontWeight: 600
    lineHeight: "1.75rem"
    letterSpacing: "-0.025em"
  caption:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 400
    lineHeight: "1rem"
  counter:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "2.25rem"
    fontWeight: 600
    lineHeight: "2.5rem"
    letterSpacing: "-0.025em"
rounded:
  "surface": "0.75rem"
  "control": "0.875rem"
  "md": "0.75rem"
  "sm": "0.625rem"
  "native-input": "0.5rem"
  "pill": "9999px"
spacing:
  "2": "8px"
  "3": "12px"
  "4": "16px"
  "5": "20px"
  "6": "24px"
  "8": "32px"
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.surface}"
    typography: "{typography.button-label}"
    rounded: "{rounded.control}"
    padding: "8px 16px"
    height: "40px"
  button-outline:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.foreground}"
    rounded: "{rounded.control}"
    padding: "8px 16px"
    height: "40px"
  button-secondary:
    backgroundColor: "{colors.secondary}"
    textColor: "{colors.foreground}"
    rounded: "{rounded.control}"
    padding: "8px 16px"
    height: "40px"
  button-ghost:
    backgroundColor: "transparent"
    textColor: "{colors.foreground}"
    rounded: "{rounded.control}"
    padding: "8px 16px"
    height: "40px"
  input:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.foreground}"
    rounded: "{rounded.control}"
    padding: "4px 12px"
    height: "44px"
  nav-active:
    backgroundColor: "{colors.accent}"
    textColor: "{colors.primary}"
    height: "44px"
  priority-critical:
    backgroundColor: "{colors.danger-soft}"
    textColor: "{colors.danger-ink}"
    rounded: "{rounded.md}"
    padding: "4px 10px"
  card:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.foreground}"
    rounded: "{rounded.surface}"
    padding: "24px"
  sla-warning:
    backgroundColor: "{colors.warning-soft}"
    textColor: "{colors.warning-ink}"
    rounded: "{rounded.control}"
    padding: "4px 10px"
  appearance-selection:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.primary}"
    rounded: "{rounded.native-input}"
    padding: "8px 14px"
    height: "40px"
---

# Design System: TracePilot

## Overview

**Creative North Star: "TracePilot operational workspace"**

A familiar operational workspace: Inter, navy text, blue selection and cool surfaces support fast reading under time pressure. Restrained red, amber and green treatments make urgency visible alongside explicit labels.

The shared shell and controls carry the visual identity across the application. Existing SVG brand assets remain in use. Product terminology remains governed by docs/PRODUCT.md.

This records the current working-copy implementation in [resources/css/app.css](resources/css/app.css), [resources/css/transitions.css](resources/css/transitions.css) and the shared Vue controls. [style.md](style.md) remains the operational rulebook; [docs/PRODUCT.md](docs/PRODUCT.md) remains the product source of truth. Surface-specific composition contracts live in [.impeccable/surfaces/dashboard.md](.impeccable/surfaces/dashboard.md) and [.impeccable/surfaces/workspace.md](.impeccable/surfaces/workspace.md). The machine-readable frontmatter uses the [DESIGN.md format](https://raw.githubusercontent.com/google-labs-code/design.md/main/docs/spec.md); `.impeccable/design.json` holds motion, depth and component-preview extensions.

**Key Characteristics:**
- Calm blue and navy identity with semantic urgency.
- Compact controls within generously separated content blocks.
- Explicit status, priority, SLA and checklist context.
- Responsive ordering that keeps the mobile work queue close to the top.

## Colors

Blue selection and navy content sit on cool neutral surfaces; operational tints carry meaning rather than decoration. The frontmatter records exact primitives. Use the semantic CSS variables so dark mode selects the appropriate values.

### Primary

- **TracePilot Blue** (`primary`): main action, focus, selected navigation and links.
- **Night Action Blue** (`primary-dark`): dark-mode action and navigation accent; navy text sits on the primary button.

### Neutral

- **Navy Ink** (`foreground`): light-mode headings and content; dark sidebar background.
- **Cool Canvas** (`background`) and **White Surface** (`surface`): page and contained content respectively; dark foreground uses the cool canvas tone.
- **Slate Metadata** (`muted-foreground`): descriptions and secondary data, with a lighter dark-mode counterpart.
- **Quiet Divider** (`border`): borders and input outlines, with a darker-mode counterpart.
- **Blue Wash** (`accent`) and **Soft Control Wash** (`secondary`): active navigation, hover feedback and supporting controls. These are blue/neutral layers, not additional brand accents.

Operational red, amber and green each have separate solid, ink, soft-background and border roles. Shared priority and SLA components use the ink/soft/border triples, with explicit dark overrides. P3 and P4 remain neutral. Solid status dots and KPI edges use the original semantic signals.

**The Meaning Before Decoration Rule.** Pair every operational color with a label, icon or numeric context.

## Typography

**Body and heading font:** Inter with the existing sans-serif fallback stack. There is no separate display face.

The type hierarchy is compact and direct. Semibold incident names and headings lead; regular metadata recedes without becoming faint. The body enables Inter features `cv02`, `cv03`, `cv04` and `cv11`; tables and counters use tabular numerals.

- **Headline:** fluid dashboard greeting; the only large introductory heading.
- **Page title:** incident and ordinary page headings.
- **Section title:** queue and card headings; some existing cards use the adjacent 20px size.
- **Body / label:** table content and fields; labels use medium weight. **Button label** separately records the semibold action style.
- **Settings heading:** default `Heading` uses 20px; its small variant uses the 18px section title. `WorkspaceHeader` provides the page-level `h1`; `Heading` uses `h2`, and `CardTitle` defaults to `h3` with an explicit `h2` option.
- **Caption:** project/customer context, table headings and compact SLA detail.
- **Counter:** desktop KPI totals; mobile totals shrink to 28px.

## Layout

A grouped sidebar separates Werkplek from Beheer; settings and user controls sit in the footer. A sticky toolbar is 64px high. Dashboard and incident detail content use a centered 1440px maximum width, 20px side padding increasing to 32px from the small breakpoint. Shared management views use `WorkspacePage` with the same 1440px width; compact forms use its 768px variant. Some older views retain the 1152px container.

Settings use a 224px navigation column beside flexible content from 1024px, with a 32px column gap and sticky navigation. On smaller screens a visible current-setting button expands the grouped navigation.

Main blocks use 24px gaps; summary grids use 20px gaps. Card content normally has 24px padding, with compact filters using 16–20px. Header actions wrap on narrow screens. Default buttons are 40px high and text inputs 44px.

The dashboard uses three desktop KPI columns with equal minimum 144px card heights and 4px semantic left edges. Below 1024px, the queue comes before the summaries and dashboard filters are expandable with a visible button; active filtering stays announced. Desktop summaries precede the queue. This ordering is a dashboard pattern, not a requirement for every page.

Incident tables combine the incident title with customer/project context, place priority early, retain SLA and checklist information, and scroll horizontally. Dashboard tables retain a 1000px minimum width. Detail timestamps stack, become two columns at 640px and four at 1280px. Reuse these responsive patterns instead of hiding operational data.

## Elevation & Depth

Borders and tonal layers do most of the structural work. Shared cards use the soft `--tracepilot-shadow`; the sidebar has no shadow. Buttons and inputs keep their small existing primitive shadows. Focus is a blue border plus a 3px translucent ring on shared buttons and inputs; native fields use the corresponding blue focus shadow.

**The State Before Spectacle Rule.** Use restrained state transitions; respect reduced motion for table rows, navigation and buttons.

Table backgrounds transition over 150ms with ease-out; buttons use 150ms color transitions. The shell retains its existing sidebar and dialog motion. The shared transition stylesheet adds 250ms disclosure, icon and appearance-selection transitions; their reduced-motion guards disable the tween while preserving state. This documentation does not claim that every legacy transition has received a reduced-motion audit.

## Shapes

The compiled implementation currently uses 12px surface corners (`rounded-xl`) and 14px control corners (`rounded-lg`), while smaller token-based corners use 12px and 10px. Native inputs/selects are overridden to 8px. Status dots and progress bars are fully rounded.

**Recorded drift:** style.md describes 14px surface corners, but the CSS binds only the large/medium/small radius tokens; Tailwind's extra-large token remains 12px. This is an implementation discrepancy, not a new surface rule. Reconcile it with style.md before extending the system; do not spread it as an intentional distinction.

## Components

### Buttons

Primary buttons are blue with white text in light mode, light blue with navy text in dark mode. Outline buttons use a card surface and fine border; secondary buttons use a soft blue wash; ghost controls provide low-priority actions. Default padding is 8px by 16px, reduced horizontally when an icon is present. Hover changes the surface or opacity rather than moving the control. Keep the shared focus ring and disabled treatment.

### Inputs / Fields

The shared input uses a quiet border, white light-mode background, translucent input-colored dark background and 44px height. Its font is 16px on narrow screens and 14px from the medium breakpoint. Native selects remain familiar and use the shared focus behavior. Preserve labels and field-associated validation messages.

### Navigation

Navigation rows are 44px high with Lucide icons and text. The active state uses blue wash, blue text and a 2px inset leading marker. Group labels are quiet sentence-case descriptions, separated by a fine divider. Mobile navigation closes after selection. Keep `aria-current` on the active link.

### Priority, status and SLA

Reuse `IssuePriorityBadge`, `IssueStatusBadge` and `IssueSlaBadge`. Priority is a compact outlined label; status pairs a solid dot with its label. SLA is a two-line pill showing the relevant milestone and remaining or overdue time. Red/amber/green tones have explicit light and dark ink, background and border variants. SLA selection behavior belongs to product logic, not a decorative choice.

### Cards / Containers

Cards have a fine border, soft shadow and separated header/content when controls need their own row. KPI cards retain equal desktop heights, a 4px colored edge, one number and one explanatory line. Compact mobile summaries retain the same meaning with a horizontal number layout.

### Incident next action

A semantic panel directly below the incident header names the next required action. Required checklist progress remains near status, priority and SLA; a separate timestamp strip provides the exact lifecycle context. Use explicit action copy and meaningful tones rather than adding a decorative headline.

### Workspace pages and headers

Reuse `WorkspacePage` for the centered content width, 24px block gaps, 20px/32px horizontal padding and bottom breathing room. `WorkspaceHeader` supplies one 24px semibold `h1`, a muted description with 24px line height, optional context chips and wrapping action controls. Its layout stacks below 640px and aligns actions to the heading on larger screens. Reuse the settings layout's grouped Account and Incidentbeheer navigation rather than creating a separate navigation pattern.

### Motion and state continuity

[resources/css/transitions.css](resources/css/transitions.css) owns the semantic variables and `t-*` hooks. Animate an existing interaction, rather than revealing static content on page load.

| Interaction | Hooks | Timing and easing | Behavior |
| --- | --- | --- | --- |
| Dashboard and extra incident filters | `.t-acc`, `data-open`, `.t-acc-panel`, `.t-acc-panel-inner` | `--acc-expand`, `--acc-collapse`: 250ms; `--acc-ease`: `cubic-bezier(0.22, 1, 0.36, 1)` | Grid track grows from `0fr` to `1fr`; inner content fades and resolves a 2px blur. Closed panels are `inert` and `aria-hidden`. Dashboard filters stay visible on desktop, including before hydration. |
| Password visibility | `.t-icon-swap`, `data-state`, `.t-icon`, `data-icon` | `--icon-swap-dur`: 250ms; `--icon-swap-ease`: `ease-in-out` | Both Lucide icons share one grid cell; opacity, 2px blur and scale cross-fade. The button retains its label, pressed state and keyboard focus. |
| Appearance selection | `.t-tabs`, `.t-tab`, `.t-tabs-pill`, `.appearance-tabs` | `--tabs-dur`: 250ms; `--tabs-ease`: `cubic-bezier(0.22, 1, 0.36, 1)` | The selection moves and resizes to the active button. First placement and resize snap without a tween. Controls remain a labeled button group with `aria-pressed`, not content tabs. |

The appearance group uses the existing muted/card/accent color variables in both themes, 12px group corners, 8px button/selection corners and 40px controls. The unmodified transition snippet's 48px radius is overridden by `.appearance-tabs`; it is not the TracePilot shape rule. A `ResizeObserver` keeps the selection aligned without measuring on every animation frame.

All three patterns retain `prefers-reduced-motion: reduce` guards. Keep the two-layer disclosure structure so closed panels have no residual padding, and preserve input values while filters close. Keep motion variables in the stylesheet rather than introducing component-specific timeouts. Documentation records implemented behavior; it does not certify a complete accessibility or dark-mode audit.

## Do's and Don'ts

### Do:
- **Do** read style.md before every UI change and reuse the shared controls.
- **Do** keep visible interface copy short, active and Dutch.
- **Do** expose incident priority, project context, SLA and required checklist progress together.
- **Do** preserve horizontal scrolling and a visible scrolling hint for wide mobile tables.
- **Do** retain visible focus states and accessible labels for icon controls.
- **Do** reuse semantic motion variables and keep reduced-motion guards and closed-panel `inert` behavior.

### Don't:
- **Don't** use color as the only status or urgency cue.
- **Don't** replace the existing SVG logos with decorative raster imagery.
- **Don't** add heavier card shadows or gradients.
- **Don't** conceal important incident columns to fit a narrow screen.
- **Don't** add page-load choreography or animate the appearance selection from zero on first paint.
