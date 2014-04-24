Generate Sitemap Library
=======================

[![Build Status](https://travis-ci.org/maki674/cake_GenerateSitemap.svg?branch=master)](https://travis-ci.org/maki674/cake_GenerateSitemap)
[![Coverage Status](https://coveralls.io/repos/maki674/cake_GenerateSitemap/badge.png)](https://coveralls.io/r/maki674/cake_GenerateSitemap)

CakePHP でサイトマップの生成を行うライブラリ (クラス) をプラグイン形式で提供します。

サイトマップについての説明: [sitemap.org](http://www.sitemaps.org/protocol.html)

License: MIT License

Description
===

サイトマッププロトコルで、1 つの sitemap.xml に記述できる URL は最大 50,000 件となっています。50,000 を超える URL を持っているサイトでは複数のサイトマップを作り、さらにサイトマップインデックスを作成しなければなりません。

このライブラリはその作業を自動化します。バッチでの生成を想定しており、HTTP リクエスト毎の生成は考慮していません。

Required
===

- CakePHP 2
- PHP ZLIB extension

How to use
===

Copy into plugin directory.

- ```/app/Plugin/GenerateSitemap```
- ```/plugins/GenerateSitemap```

Configure your settings. (eg. app/Config/bootstrap.php)

```php
Configure::write('GenerateSitemap', array(
  'baseUrl' => 'http://www.example.com/', // your top directory, required backslash at the end.
  'tmpDir' => TMP, // work directory
  'saveDir' => APP . WEBROOT_DIR, // sitemap is placed here
  'prefix' => 'sitemap' // filename prefix (eg. sitemap -> sitemap.xml, sitemap0.xml.gz)
);
```

In code,

```php
App::uses('GenerateSitemap', 'GenerateSitemap.Lib');

$GenerateSitemap = new GenerateSitemap();

/**
  * @param string $loc URL Max length is 2048.
  * @param string $lastmod W3C Datetime or YYYY-MM-DD format
  * @param string $changefreq Options: always / hourly / daily / weekly / monthly / yearly / never
  * @param float $priority Between 0.0 AND 1.0
  */
$GenerateSitemap->append('http://www.example.com/controller/action',
  '2014-01-01', 'always', '1.0');

$GenerateSitemap->publish();
```

publish() を実行すると、サイトマップインデックスとサイトマップが saveDir に生成されます。50,000 件を超える場合、自動的に複数のサイトマップが作成され、サイトマップインデックスにリンクが挿入されます。なお、50,000 件を超えなくても、サイトマップインデックスファイルが作成されます。

以下はサンプルです。

sitemap.xml
----
```xml
<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <sitemap>
    <loc>http://www.example.com/sitemap0.xml.gz</loc>
    <lastmod>2014-10-10T19:00:00+00:00</lastmod>
  </sitemap>
</sitemapindex>
```

sitemap0.xml.gz
----
```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>http://www.example.com/controller/action</loc>
    <lastmod>2014-01-01</lastmod>
    <changefreq>always</changefreq>
    <priority>1.0</priority>
  </url>
</urlset>
```

Method
====
- ```append()```: URL を追加します
- ```publish()```: サイトマップを生成します。戻り値はサイトマップインデックスファイルの絶対パスです
- ```clear()```: saveDir のサイトマップすべてを削除します。prefix に指定したファイルを前方一致で削除します

その他、自動テスト用のメソッドが用意されています。
