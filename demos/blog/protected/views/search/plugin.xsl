<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl"
	exclude-result-prefixes="php">
	<xsl:output method="xml" />

	<xsl:template match="/data">
		<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/" xmlns:moz="http://www.mozilla.org/2006/browser/search/">
			<ShortName>Yii Blog Demo Search</ShortName>
			<Description></Description>  
			<InputEncoding>utf-8</InputEncoding>  
			<Image width="16" height="16" type="image/x-icon">http://yiiframework.com/favicon.ico</Image>  
			<Url type="text/html" method="method" template="searchURL"/>
			<Url type="application/x-suggestions+json" template="suggestionURL"/>
			<moz:SearchForm>searchFormURL</moz:SearchForm>
		</OpenSearchDescription>
	</xsl:template>
</xsl:stylesheet>