# SitemapGeneratorCommand

**Yii 1** console command to generate sitemaps.

### Usage:

```bash
./yiic sitemapgenerator [OPTIONS]
```

### Customizing:

Command accepts these options for customizing:

Option | Default value | Description 
:---:|:---:|:---
**PARAMS:** |  | main options
| table | ``` 'url_alias' ``` | Database table with urls data |
| baseUrl | ``` '' ``` | Base url for urls (also applies to xml files, listed in xml index file) |
| saveAlias | ``` 'webroot.xml-sitemap' ``` | Yii alias that used to save xml files. If directory is not exist - **it will be created (0775 permissions)**
**FIELDS:** |  | database data association
field[url] | ``` 'alias' ``` | database column that contains url
field[lastmod] | ``` NULL ``` | database column that contains last modification date

**Example:**

```bash
./yiic sitemapgenerator table=urlAliases baseUrl=http://i.kenfin.ru saveAlias=webroot field[url]=urlAliases_alias
```