---
name: GuidancePulse
colors:
  surface: '#f7f9fb'
  surface-dim: '#d8dadc'
  surface-bright: '#f7f9fb'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f4f6'
  surface-container: '#eceef0'
  surface-container-high: '#e6e8ea'
  surface-container-highest: '#e0e3e5'
  on-surface: '#191c1e'
  on-surface-variant: '#434655'
  inverse-surface: '#2d3133'
  inverse-on-surface: '#eff1f3'
  outline: '#737686'
  outline-variant: '#c3c6d7'
  surface-tint: '#0053db'
  primary: '#004ac6'
  on-primary: '#ffffff'
  primary-container: '#2563eb'
  on-primary-container: '#eeefff'
  inverse-primary: '#b4c5ff'
  secondary: '#006c49'
  on-secondary: '#ffffff'
  secondary-container: '#6cf8bb'
  on-secondary-container: '#00714d'
  tertiary: '#46566c'
  on-tertiary: '#ffffff'
  tertiary-container: '#5e6e85'
  on-tertiary-container: '#e9f0ff'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#dbe1ff'
  primary-fixed-dim: '#b4c5ff'
  on-primary-fixed: '#00174b'
  on-primary-fixed-variant: '#003ea8'
  secondary-fixed: '#6ffbbe'
  secondary-fixed-dim: '#4edea3'
  on-secondary-fixed: '#002113'
  on-secondary-fixed-variant: '#005236'
  tertiary-fixed: '#d3e4fe'
  tertiary-fixed-dim: '#b7c8e1'
  on-tertiary-fixed: '#0b1c30'
  on-tertiary-fixed-variant: '#38485d'
  background: '#f7f9fb'
  on-background: '#191c1e'
  surface-variant: '#e0e3e5'
typography:
  display-lg:
    fontFamily: Inter
    fontSize: 48px
    fontWeight: '700'
    lineHeight: '1.1'
    letterSpacing: -0.02em
  display-lg-mobile:
    fontFamily: Inter
    fontSize: 36px
    fontWeight: '700'
    lineHeight: '1.2'
  headline-lg:
    fontFamily: Inter
    fontSize: 30px
    fontWeight: '600'
    lineHeight: 38px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  label-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 20px
  label-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.05em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 4px
  xs: 4px
  sm: 8px
  md: 16px
  lg: 24px
  xl: 40px
  container-margin: 32px
  gutter: 20px
---

## Brand & Style
The design system is engineered for a high-stakes academic environment, balancing the precision of an enterprise tool with the approachability of a modern web application. It adopts a **Modern Minimalist** aesthetic, heavily influenced by the high-density utility of developer tools and the polished finish of premium SaaS platforms.

The UI is characterized by:
- **Exceptional Clarity:** Information density is carefully managed to ensure test-takers and administrators remain focused.
- **Precision Engineering:** Subtle borders and a strict grid system create an atmosphere of reliability and officiality.
- **Tactile Softness:** Despite the professional tone, large corner radii and soft shadows ensure the interface feels welcoming to students.
- **Dynamic Feedback:** Subtle glassmorphism and smooth transitions provide a responsive feel, signaling a high-performance, real-time system.

## Colors
This design system utilizes a functional color palette where color is used to denote hierarchy and system status.

- **Primary (Blue):** Used for primary actions, active navigation states, and focused inputs.
- **Accent/Success (Emerald):** Reserved for positive completion states, score improvements, and final submissions.
- **Neutral (Slate):** Used for typography and structural borders. 
- **Surface Strategy:** In light mode, surfaces use pure white with `#F8FAFC` backgrounds to create "island" layouts. In dark mode, a deep navy-charcoal palette maintains legibility while reducing eye strain during long examination periods.

## Typography
The system relies on **Inter** for its neutral, highly legible characteristics. 

- **Hierarchy:** Use `display-lg` exclusively for dashboard welcomes or exam titles. 
- **Readability:** For exam questions, use `body-lg` to ensure students can read long-form text without fatigue.
- **Utility:** `label-sm` is used for uppercase "overlines" above section headers and within status badges.
- **Weights:** Use Semi-Bold (600) for interactive elements and Regular (400) for all descriptive content.

## Layout & Spacing
The design system follows a **12-column fluid grid** for administrative dashboards and a **centered fixed-width container (max-width: 800px)** for examination modules to minimize horizontal eye tracking.

- **Standard Spacing:** Use an 8px base grid.
- **Vertical Rhythm:** Content sections should be separated by `xl` spacing to maintain the minimalist "breathable" feel.
- **Sidebars:** The sidebar should default to 260px and collapse to 64px, pushing content rather than overlapping it.

## Elevation & Depth
Depth is communicated through **Tonal Layering** and **Soft Ambient Shadows**.

- **Level 0 (Background):** Slate-50 (Light) or Slate-950 (Dark).
- **Level 1 (Cards/Surface):** White/Slate-900 with a 1px border of Slate-200/800.
- **Level 2 (Dropdowns/Modals):** Subtle 15% opacity shadow with a 20px blur, plus a semi-transparent backdrop blur (12px) to create a glass effect.
- **Interactive Depth:** Buttons should use a subtle 1px "inner highlight" on the top edge to simulate a slightly tactile, raised surface.

## Shapes
The design system uses a pronounced roundedness to soften the academic intensity of the application.

- **Standard Elements:** 0.5rem (Buttons, Inputs).
- **Containers:** 1rem (Cards, Tables, Modals).
- **Pill Elements:** Full rounding for status badges and tags.
- **Selection States:** Use rounded-md (0.375rem) for items inside lists or navigation menus.

## Components
Consistent implementation of components ensures a seamless transition between administrative and student views.

- **Sidebar:** Navigation items use a transparent background that shifts to a subtle primary-tinted gray on hover. Active states use a solid primary blue left-accent bar (4px).
- **Data Grids:** Horizontal lines only (no vertical borders). Header row uses `label-sm` in a muted slate color. Row hover states should trigger a very subtle background tint change.
- **Forms:** Inputs utilize a "Modern Box" style with a 1px border. On focus, the border transitions to Primary Blue with a 3px outer halo of 10% opacity blue.
- **Buttons:** 
  - *Primary:* Solid Blue with white text. 
  - *Secondary:* Ghost style with Primary Blue text, appearing as a soft gray on hover.
  - *Danger:* Outline Red for destructive actions like "End Exam."
- **Status Badges:** Use a "Soft Fill" approach—10% opacity background of the status color with 100% opacity text of the same color (e.g., Emerald background + Emerald text for "Passed").
- **Cards:** Always include a subtle 1px border. For exam questions, cards should have an elevated shadow on hover to signal interactivity.