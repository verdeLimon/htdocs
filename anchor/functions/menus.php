<?php

/*
  Theme functions for menus
 */

function make_url($menu) {
    if ($menu->tipo == 'pagina') {
        $pg = Pagina::find($menu->url);
        return base_url() . $pg->categoria->slug . '/' . $pg->slug;
    } else {
        return $menu->url;
    }
}

function has_children($rows, $id) {
    foreach ($rows as $row) {
        if ($row->parent_menuitem_id == $id)
            return true;
    }
    return false;
}

function build_main_menu($rows, $parent = 0) {
    $result = "";
    if ($parent != 0) {
        $result .= '<ul class="dropdown-menu">';
    }
    foreach ($rows as $row) {
        if ($row->parent_menuitem_id == $parent) {
            $result .= "<li class='dropdown'>";
            if (has_children($rows, $row->id)) {
                $result .= "<a href='" . make_url($row) . "' class='dropdown-toggle' data-toggle='dropdown' role='button' aria-haspopup='true' aria-expanded='false'>{$row->texto}";
                $result .= "<span class='caret'></span>";
            } else {
                $result .= "<a href='" . make_url($row) . "'>{$row->texto}";
            }
            $result .= "</a>";
            if (has_children($rows, $row->id)) {
                $result .= build_main_menu($rows, $row->id);
            }
            $result .= "</li>";
        }
    }
    $result .= "</ul>";

    return $result;
}

//echo build_menu($menu);

function get_menu($menu) {
    $menus = Menu::first(array('conditions' => "posicion = '$menu' and estado = '1'"));
    return $menus->menuitems_m;
}

/*
  Object props
 */

function menu_id($menu_item = null) {
    if (is_array($menu_item))
        return $menu_item['id'];
    return ($menu_item ? $menu_item->id : Registry::prop('menu_item', 'id'));
}

function menu_url($menu_item = null) {
    if (is_array($menu_item))
        $menu_item = get_menu_item('id', $menu_item);
    if (!$menu_item) {
        $menu_item = Registry::get('menu_item');
    }
    if ($menu_item) {
        return $menu_item->uri();
    }
}

function menu_relative_url($menu_item = null) {
    if (is_array($menu_item))
        $menu_item = get_menu_item('id', $menu_item);
    if (!$menu_item) {
        $menu_item = Registry::get('menu_item');
    }
    if ($menu_item) {
        return $menu_item->relative_uri();
    }
}

function menu_name($menu_item = null) {
    if (is_array($menu_item))
        return $menu_item['name'];
    return ($menu_item ? $menu_item->name : Registry::prop('menu_item', 'name'));
}

function menu_title($menu_item = null) {
    if (is_array($menu_item))
        return $menu_item['title'];
    return ($menu_item ? $menu_item->title : Registry::prop('menu_item', 'title'));
}

function menu_active($menu_item = null) {
    if (is_array($menu_item))
        $menu_item = get_menu_item('id', $menu_item);
    if (!$menu_item) {
        $menu_item = Registry::get('menu_item');
    }
    if ($menu_item) {
        return $menu_item->active();
    }
}

function menu_parent($menu_item = null) {
    if (is_array($menu_item))
        return $menu_item['parent'];
    return ($menu_item ? $menu_item->parent : Registry::prop('menu_item', 'parent'));
}

function menu_has_children($parent = null) {
    foreach (get_menus_children($parent) as $c)
        return 1; // Returns true if there is atleast 1 child
    return 0;
}

function get_menus_children($parent = null) {
    $menu_item = menu_id($parent);
    $menu = Registry::get('menu');
    $menu->rewind();
    $children = array();

    foreach ($menu as $item) {
        if ($item->parent == $menu_item)
            $children[] = $item;
    }

    return $children;
}

function is_child_active($parent = null) {
    foreach (get_menus_children($parent) as $child) {
        if ($child->active())
            return 1;
    }
    return 0;
}

/*
  Menu Item Getters
 */

function get_menu_item($feature_type, $menu_feature) {
    if (is_array($menu_feature))
        $menu_feature = $menu_feature[$feature_type];
    $menu = clone Registry::get('menu');
    foreach ($menu as $page) {
        if ($page->{$feature_type} == $menu_feature)
            return $page;
    }
}

/*
  HTML Builders
 */

function menu_render($params = array()) {
    $html = '';
    $menu = Registry::get('menu');

    // options
    $parent = isset($params['parent']) ? $params['parent'] : 0;
    $class = isset($params['class']) ? $params['class'] : 'active';
    $index = isset($params['index']) ? $params['index'] : 0;

    foreach ($menu as $item) {
        if ($item->parent == $parent) {
            $attr = array();

            if ($item->active())
                $attr['class'] = $class;

            $html .= '<li>';
            $html .= Html::link($item->relative_uri(), $item->name, $attr);
            $html .= menu_render(array('parent' => $item->id, 'index' => $menu->key()));
            $html .= '</li>' . PHP_EOL;
        }
    }
    // Reset our index before returning
    $menu->rewind();
    $menu->next();
    while ($index > 1) {
        $menu->next();
        $index--;
    }

    if ($html)
        $html = PHP_EOL . '<ul>' . PHP_EOL . $html . '</ul>' . PHP_EOL;

    return $html;
}
