<?php
namespace com\sergiosgc\ui\menu;
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
class FromConfigFactory
{
    public static function create($name) {
        $result = new Menu();
        $indexes = \com\sergiosgc\Facility::get('config')->getKeys($name . '.item');
        foreach ($indexes as $index) {
            $itemLine = \com\sergiosgc\Facility::get('config')->get(sprintf('%s.item.%s', $name, $index));
            switch (substr($itemLine, 0, 2)) {
            case 'l:': /* Leaf */
                $href = explode(' ', substr($itemLine, 2), 2);
                $label = $href[1];
                $href = $href[0];
                $result->addItem(new Leaf($label, $href));
                break;
            case 'm:': /* Menu */
                $href = explode(' ', substr($itemLine, 2), 3);
                $configId = $href[1];
                $label = $href[2];
                $href = $href[0];
                $result->addItem($submenu = self::create($configId));
                $submenu->setLabel($label);
                $submenu->setHref($href);
                break;
                default: throw new Exception(sprintf('Unknown type for menu item %s', $itemLine));
            }
        }
        return $result;
    }
}
?>

