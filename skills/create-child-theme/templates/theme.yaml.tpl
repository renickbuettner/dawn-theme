name: '{{NAME}}'
description: '{{DESCRIPTION}}'
author: '{{AUTHOR}}'
homepage: '{{HOMEPAGE}}'
code: {{SLUG}}

# Dawn's DIRECTORY name, not its `code:` — and install-dependent (`renick-dawn`
# from the Marketplace). Check with `php artisan theme:list`.
parent: {{DAWN_DIR}}

# Same hard dependency as Dawn — October installs it on first theme install.
require:
    - 'Renick.Content'

# Frontend strings come from Dawn's seeded RainLab.Translate messages, so ship
# none. Distributing this child WITHOUT Dawn's data means copying its translate/.

# Form fields are NOT inherited — copy what the child needs. The dawn.* keys
# resolve because October boots the parent's lang/ path too.
form:
    fields:
        brand_name:
            tab: dawn.theme.form.branding_tab
            label: dawn.theme.form.brand_name.label
            comment: dawn.theme.form.brand_name.comment
            type: text
            default: '{{NAME}}'
        view_transitions:
            tab: dawn.theme.form.behavior_tab
            label: dawn.theme.form.view_transitions.label
            comment: dawn.theme.form.view_transitions.comment
            type: checkbox
            default: true
