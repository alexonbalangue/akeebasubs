<?php die() ?>

================================================================================
Akeeba Subscriptions - Signed PDF support
================================================================================

--------------------------------------------------------------------------------
The executive summary
--------------------------------------------------------------------------------

Upload your X.509 certificate as certificate.cer in this directory to sign your
PDF invoices.

If you have a separate secret key, a password protected secret key and/or you
need to provide extra certificates please read the rest of the text below.

--------------------------------------------------------------------------------
The long story
--------------------------------------------------------------------------------

Akeeba Subscriptions 3.0.0 and later allow you to create signed invoice PDF
files. Signed PDFs are cryptographically signed to:
a. prevent disputes over their authenticity; and
b. prevent being tampered with

IMPORTANT! You MUST have the OpenSSL enabled in PHP if you want to use PDF
           signing with Akeeba Subscriptions.

In order for Akeeba Subscriptions to be able to sign the PDF files it generates
you need to provide it with the X.509 certificate (a.k.a. "SSL certificate",
"singing certificate", or "S/MIME certificate") it will use. You can obtain
such certificates for a small fee from most Certificate Authorities (CAs) such
as VeriSign, Thawte, GeoTrust etc. Most CAs sell them as "secure identity" or
"secure mail" certificates. Before buying such a certificate please make check
with your CA that it can be used for PDF signing. Alternatively, you can get a
free or low cost certificate from from Comodo or StartSSL.

Following your Certificate Authority's instructions you will have to export your
certificate in PEM format (.cer or .crt file). PKCS#12 files (.pfx or .p12) can
NOT be used with Akeeba Subscriptions. If you only have a .pfx or .p12 file you
will have to ask your Certificate Authority about the proper way to export it
to PEM format.

Upload your PEM format certificate inside your site's
administrator/components/com_akeebasubs/assets/tcpdf/certificates directory
(the directory where this file can be found). Then go to the component's
Options, Integrated Invoicing and set the "Certificate file" to the name of the
file you uploaded. If you are unsure, rename your certificate file to
certificate.cer and type in certificate.cer in the "Certificate file" area. If
your certificate file can not be found or is not readable your PDF invoices
will not be signed.

Most certificates include both the public and private signature. If you need
to upload a different certificate file for your secret key you will also need to
set the "Certificate secret key (optional)" option to the name of your secret
key's certificate file, e.g. secret.cer. If your secret key is protected with a
password please enter it in the "Certificate secret key password (optional)"
parameter.

If you need to provide extra certificates (for example, your Certificate
Authority's root certificate) upload them in a different file, e.g. named
extra.cer, and set its name in the "Extra certificates (optional)" parameter.

Do note that most commercial Certificate Authorities are not in the Adobe
Approved Trust List (AATL). Even though your PDFs will be signed, Adobe Reader
will display them with the note that the signature status is "UNKNOWN". This is
normal. Your clients will need to add your public certificate to their copy of
Adobe Reader to verify your signature.

Finally, please note that many third part PDF reader applications, such as
Apple's Preview app, do not understand signatures and will not display the
singature information at all. Therefore we strongly recommend that you tell
your clients to install and use Adobe Reader to view your PDF files.

More information can be found in our documentation.