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
    typography: "{typography.label}"
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
---

# Design System: TracePilot

## Overview

**Creative North Star: "TracePilot operational workspace"**

A familiar operational workspace: Inter, navy text, blue selection and cool surfaces support fast reading under time pressure. Restrained red, amber and green treatments make urgency visible alongside explicit labels.

The shared shell and controls carry the visual identity across the application. Existing SVG brand assets remain in use; this pass introduced no shipping raster assets. Product terminology remains governed by docs/PRODUCT.md.

This records the implementation in [resources/css/app.css](resources/css/app.css) and the shared Vue controls. [style.md](style.md) remains the operational rulebook; [docs/PRODUCT.md](docs/PRODUCT.md) remains the product source of truth. The dashboard composition contract lives in [.impeccable/surfaces/dashboard.md](.impeccable/surfaces/dashboard.md).

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
- **Body / label:** table content and controls; labels use medium weight, buttons semibold.
- **Caption:** project/customer context, table headings and compact SLA detail.
- **Counter:** desktop KPI totals; mobile totals shrink to 28px.

## Layout

A grouped sidebar separates Werkplek from Beheer; settings and user controls sit in the footer. A sticky toolbar is 64px high. Dashboard and incident detail content use a centered 1440px maximum width, 20px side padding increasing to 32px from the small breakpoint. Dense management views also use the established 1152px container.

Main blocks use 24px gaps; summary grids use 20px gaps. Card content normally has 24px padding, with compact filters using 16–20px. Header actions wrap on narrow screens. Default buttons are 40px high and text inputs 44px.

The dashboard uses three desktop KPI columns with equal minimum 144px card heights and 4px semantic left edges. Below 1024px, the queue comes before the summaries and dashboard filters are expandable with a visible button; active filtering stays announced. Desktop summaries precede the queue. This ordering is a dashboard pattern, not a requirement for every page.

Incident tables combine the incident title with customer/project context, place priority early, retain SLA and checklist information, and scroll horizontally. Dashboard tables retain a 1000px minimum width. Detail timestamps stack, become two columns at 640px and four at 1280px. Reuse these responsive patterns instead of hiding operational data.

## Elevation & Depth

Borders and tonal layers do most of the structural work. Shared cards use the soft `--tracepilot-shadow`; the sidebar has no shadow. Buttons and inputs keep their small existing primitive shadows. Focus is a blue border plus a 3px translucent ring on shared buttons and inputs; native fields use the corresponding blue focus shadow.

**The State Before Spectacle Rule.** Use restrained state transitions; respect reduced motion for table rows, navigation and buttons.

Table backgrounds transition over 150ms with ease-out; buttons use 150ms color transitions. The shell retains its existing sidebar and dialog motion. This documentation does not claim that every legacy transition has received a reduced-motion audit.

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

Visual verification for this redesign covered dashboard, incident list and incident detail viewports, plus a dark dashboard. Management, reports, settings and authentication inherited token/control changes without a complete visual matrix. Production build, typecheck, targeted lint and 57 backend tests passed. The final correction batch passed build, typecheck and lint; the reviewer confirmed the mobile hierarchy and muted contrast fixes. This is not a claim of a complete accessibility or dark-mode audit.

## Do's and Don'ts

### Do:
- **Do** read style.md before every UI change and reuse the shared controls.
- **Do** keep visible interface copy short, active and Dutch.
- **Do** expose incident priority, project context, SLA and required checklist progress together.
- **Do** preserve horizontal scrolling and a visible scrolling hint for wide mobile tables.
- **Do** retain visible focus states and accessible labels for icon controls.

### Don't:
- **Don't** use color as the only status or urgency cue.
- **Don't** replace the existing SVG logos with decorative raster imagery.
- **Don't** add heavier card shadows or gradients.
- **Don't** conceal important incident columns to fit a narrow screen.
