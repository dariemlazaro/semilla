CONTENTS
--------
 * INTRODUCTION
 * REQUIREMENTS
 * INSTALLATION
 * CONFIGURATION

INTRODUCTION
------------

This module allows you to generate the following printer-friendly versions
of any node:

    * Web page printer-friendly version (at /node/<nid>/printable/print)
    * PDF version (at /node/<nid>/printable/pdf)

where nid is the node id of content to render.

REQUIREMENTS
------------

 * This module is depends upon PDF Generator API
    (https://www.drupal.org/project/pdf_api)

INSTALLATION
------------

(This will simplify a lot once
https://www.drupal.org/project/drupal/issues/2494073 is fixed).

- Download this module either using composer or by getting it directly from
  drupal.org:

  composer require drupal/printable

- Install this module's composer dependencies - they can be found in
  composer.json in the project directory. Run composer require for each
  dependency from the Drupal root:

  composer require "wa72/htmlpagedom": "1.3.*"
  composer require "smalot/pdfparser": "*"

- Enable printable:

  drush en -y printable

PDF GENERATION
--------------

- Download and install the pdf_api module. Check the contents of its
  composer.json and run composer require from the Drupal root for each
  library, as was done for this module above.

- Enable this module's PDF support, which will also now be able to enable
  pdf_api.

  drush en -y printable_pdf

- Install a library for generating PDFs (mPDF, TCPDF, wkhtmltopdf and dompdf
  are supported. Test status at 18 January 2018 is as follows (Ubuntu Xenial
  VM):
  - mPDF: Needs more testing.
  - TCPDF: Just works.
  - wkhtmltopdf: Fails with an error that print_api doesn't send back or
    display. I've opened an issue with print_api regarding suppling a patch
    or taking over maintaining the module.
  - dompdf: Needs more testing.

CONFIGURATION
-------------

- Configure your PDF library at /admin/config/user-interface/printable/pdf.
  For wkhtmltopdf, after you submit the form with wkthmltopdf selected, the
  form will gain an extra field allowing you to enter the path to the binary.
  Fill this field and submit again.

- Under /admin/config/user-interface/printable/linksi/pdf, click on the PDF
  second level tab and choose where PDF links should appear.
