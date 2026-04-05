<?php
/**
 * LightboX 灯箱插件
 * 
 * @package LightboX
 * @author Link
 * @version 1.2.0
 * @link https://atlinker.cn
 */
class LightboX_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件
     */
    public static function activate()
    {
        $assetsPath = __DIR__ . '/assets';
        if (!is_dir($assetsPath) || !file_exists($assetsPath . '/js/lightbox.js')) {
            throw new Typecho_Plugin_Exception('请将 lightboX .js文件放置于插件目录下的 assets 文件夹中');
        }

        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('LightboX_Plugin', 'contentFilter');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('LightboX_Plugin', 'footer');
        
        return _t('LightboX已激活');
    }

    public static function deactivate()
    {
        return _t('插件已禁用');
    }

    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $enable = new Typecho_Widget_Helper_Form_Element_Radio('enable',
            array('1' => '开启', '0' => '关闭'),
            '1', '启用灯箱效果', '默认将所有带有.lightbox-img类的图片绑定灯箱，你也可以为图片添加.no-lightbox类让指定图片不绑定灯箱');
        $form->addInput($enable);

        $autoAddClass = new Typecho_Widget_Helper_Form_Element_Radio('auto_add_class',
            array('1' => '是', '0' => '否'),
            '1', '自动添加 lightbox-img 类', '为所有 &lt;img&gt; 添加 class="lightbox-img"');
        $form->addInput($autoAddClass);

        $removeAWrapper = new Typecho_Widget_Helper_Form_Element_Radio('remove_a_wrapper',
            array('1' => '是', '0' => '否'),
            '0', '移除图片的 &lt;a&gt; 标签', '开启后，强制将带有链接跳转的图片也绑定灯箱');
        $form->addInput($removeAWrapper);

        $selector = new Typecho_Widget_Helper_Form_Element_Text('selector',
            NULL, '.lightbox-img:not(.no-lightbox)', '图片选择器', '不填则默认 .lightbox-img:not(.no-lightbox)，通过DOM定位图片元素适配一些结构特殊的主题<br>比如填写#main-content img将id为main-content的元素内所有img标签绑定灯箱');
        $form->addInput($selector);
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form) {}

    /**
     * 输出 JS 到底部
     */
    public static function footer()
    {
        $options = Typecho_Widget::widget('Widget_Options');
        $enable = $options->plugin('LightboX')->enable;
        if ($enable === '0') {
            return;
        }

        $pluginDir = $options->pluginUrl . '/LightboX';
        $selector = $options->plugin('LightboX')->selector;
        if (empty($selector)) {
            $selector = '.lightbox-img';
        }

        // 加载 lightbox.js
        echo '<script src="' . $pluginDir . '/assets/js/lightbox.js" data-no-instant></script>' . "\n";

        // 初始化脚本（兼容 InstantClick）
        echo '<script data-no-instant>' . "\n";
        echo "// LightboX 初始化函数\n";
        echo "function initLightbox() {\n";
        echo "    if (typeof Lightbox !== 'undefined' && Lightbox.init) {\n";
        echo "        Lightbox.init('" . addslashes($selector) . "');\n";
        echo "        console.log('LightboX 已初始化，选择器：" . addslashes($selector) . "');\n";
        echo "    } else {\n";
        echo "        console.warn('LightboX 警告：Lightbox 对象未就绪，请检查 lightbox.js 是否加载');\n";
        echo "    }\n";
        echo "}\n\n";

        echo "if (typeof InstantClick !== 'undefined') {\n";
        echo "    // InstantClick 环境：监听 change 事件，每次页面切换后重新初始化\n";
        echo "    InstantClick.on('change', function() {\n";
        echo "        initLightbox();\n";
        echo "    });\n";
        echo "    // 首次加载：如果 DOM 已就绪则立即初始化，否则等待 DOMContentLoaded\n";
        echo "    if (document.readyState === 'loading') {\n";
        echo "        document.addEventListener('DOMContentLoaded', initLightbox);\n";
        echo "    } else {\n";
        echo "        initLightbox();\n";
        echo "    }\n";
        echo "} else {\n";
        echo "    // 普通环境：等待 DOMContentLoaded\n";
        echo "    document.addEventListener('DOMContentLoaded', initLightbox);\n";
        echo "}\n";
        echo "</script>\n";
    }

    /**
     * 文章内容过滤：自动添加类 / 移除 <a>
     */

    public static function contentFilter($content, $widget, $last)
    {
    $options = Typecho_Widget::widget('Widget_Options');
    $enable = $options->plugin('LightboX')->enable;
    if ($enable === '0' || empty($content)) {
        return $content;
        }

        $autoAddClass = $options->plugin('LightboX')->auto_add_class;
        $removeAWrapper = $options->plugin('LightboX')->remove_a_wrapper;

        if ($autoAddClass !== '1' && $removeAWrapper !== '1') {
        return $content;
        }

        $dom = new DOMDocument();
        $content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $images = $dom->getElementsByTagName('img');
        foreach ($images as $img) {
        $parent = $img->parentNode;
        
        // 如果需要移除 <a> 包裹
        if ($removeAWrapper === '1' && $parent && $parent->nodeName === 'a') {
            $a = $parent;
            $a->parentNode->replaceChild($img, $a);
            // 更新 parent 引用
            $parent = $img->parentNode;
        }
        
        // 如果需要自动添加类名
        if ($autoAddClass === '1') {
            // 检查当前图片是否仍被 <a> 标签包裹（移除开关未开启或未生效时）
            $currentParent = $img->parentNode;
            if ($currentParent && $currentParent->nodeName === 'a') {
                // 如果被 a 包裹，则跳过添加类（不绑定灯箱）
                continue;
            }
            $class = $img->getAttribute('class');
            $classes = array_filter(explode(' ', $class));
            if (!in_array('lightbox-img', $classes)) {
                $classes[] = 'lightbox-img';
                $img->setAttribute('class', implode(' ', $classes));
            }
        }
    }

    $body = $dom->getElementsByTagName('body')->item(0);
    if ($body) {
        $newContent = '';
        foreach ($body->childNodes as $child) {
            $newContent .= $dom->saveHTML($child);
            }
            return $newContent;
        }
        return $dom->saveHTML();
    }
}
