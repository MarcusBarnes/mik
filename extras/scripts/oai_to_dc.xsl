<xsl:stylesheet version="1.0"  xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/"
  exclude-result-prefixes="dc oai_dc" >

<!-- XSLT stylesheet that removes the elements other than those under the oai_dc tree in
     OAI-PMH records (i.e., removes everything under the 'header' tree). -->

<xsl:output method="xml" encoding="utf-8" indent="yes"/>

<!-- Remove the header tree by applying an empty template to it. -->
<xsl:template match="header"/>

<xsl:template match="oai_dc:dc">
  <oai_dc:dc xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai_dc/  http://www.openarchives.org/OAI/2.0/oai_dc.xsd">
    <xsl:copy-of select="*"/>
  </oai_dc:dc>
</xsl:template>

</xsl:stylesheet>
