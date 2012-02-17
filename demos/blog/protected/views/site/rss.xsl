<?xml version="1.0" encoding="utf-8" ?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl"
	exclude-result-prefixes="php">
	<xsl:output method="xml" />
		
	<xsl:template match="/data">
		<rss version="2.0">
			<channel>
				<title>Yii Blog Demo</title>
				<link>http://yiiframework.com</link>
				<description>feed the fish</description>
				<pubDate>
					<xsl:value-of select="php:function('date', 'r')" />
				</pubDate>
				
				<xsl:apply-templates match="posts/Post" />
			</channel>
		</rss>
	</xsl:template>
	
	<xsl:template match="Post">
		<item>
			<title><xsl:value-of select="@title"/></title>
			<pubDate><xsl:value-of select="php:function('date', 'r', number(@create_time))"/></pubDate>
			<category><xsl:value-of select="@tags" /></category>
			<description>
				<xsl:text disable-output-escaping="yes">&lt;![CDATA[</xsl:text>
				<xsl:value-of select="@content" />
				<xsl:text disable-output-escaping="yes">]]&gt;</xsl:text>
			</description>
		</item>
	</xsl:template>
</xsl:stylesheet>