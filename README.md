# LightboX

仅需15kb，不需要任何依赖库实现typecho图片灯箱功能

无需设置一键启用，支持Instantclick

## 食用方法

⚠️将压缩包放入typecho根目录```/usr/plugins/```解压，将文件夹名称由```typecho-LightboX```改为```LightboX```

随后在typecho后台插件管理启用LightboX即可

## 注意 ⚠️⚠️⚠️

如果启用后无法生效，请检查使用的主题模板```footer.php```文件或类似的页脚文件是否包含以下代码

```php
<?php $this->footer(); ?>
```

如果没有加上即可
