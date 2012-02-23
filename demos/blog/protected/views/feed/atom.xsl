<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl"
	exclude-result-prefixes="php">
	<xsl:output method="xml" />
	
	<xsl:template match="/data">
		<feed xmlns="http://www.w3.org/2005/Atom">
			<author>
				<name>The Yii Community</name>
			</author>
			<title>Yii Blog demo</title>
			<id>http://yiiframework.com/</id>
			<updated>
				<xsl:value-of select="php:function('date', 'c')" />
			</updated>
			
			<xsl:apply-templates match="posts/Post" />
		</feed>
	</xsl:template>
	
	<xsl:template match="Post">
		<entry xmlns="http://www.w3.org/2005/Atom">
			<title><xsl:value-of select="@title"/></title>
			<link href="http://yiframework.com/" />
			<id><xsl:value-of select="@id"/></id>
			<updated><xsl:value-of select="php:function('date', 'c', number(@update_time))"/></updated>
			<content><xsl:value-of select="@content" /></content>
		</entry>
	</xsl:template>
</xsl:stylesheet>