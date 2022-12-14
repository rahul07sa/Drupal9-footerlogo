# Alshaya AMP White Label Subtheme

This Alshaya AMP White Label Subtheme serves as a demonstration for how you can provide a
subtheme that extends the AMP Base theme to provide custom styles.

Please refer to the README at the root of amptheme for full installation
instructions to get your site ready for AMP.

To create your own custom subtheme, make sure to do the following additional
steps beyond what you would normally do to create a subtheme:
- Create a theme that has the value of `base theme:` set to `amptheme`. This
  lets the AMP Base theme provide markup defaults that carry through to a custom
  theme.

Make sure to follow guidelines at https://www.ampproject.org/ on allowed styles
and markup in order to have valid HTML. Please note that CSS and JS added in a
libraries.yml file will not be loaded on AMP-enabled pages.

Install your custom theme and set it as the theme that will be used on
AMP-enabled pages at `/admin/config/content/amp`: the AMP module must be
installed to view this configration page.
