<?php
/*
=====================================================
 Forge Forum Engine — İzin yardımcıları
-----------------------------------------------------
 File: engine/modules/forum/permission_helpers.php

 Kategori erişim kuralları tek bir yerden yönetilir.
 `permissions` alanı:
   - "all"  : herkese açık
   - ""     : boş kayıtlar eski kurulumlardan gelebilir, herkese açık kabul edilir
   - "1,2"  : yalnızca listelenen kullanıcı grupları

 Yönetici (grup 1) her zaman erişebilir. Diğer gruplar listede olmak zorundadır.
=====================================================
*/
if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

if (!function_exists("forum_current_group_id")) {
    /**
     * Geçerli kullanıcının grup kimliği. Misafir varsayılan olarak 5'tir.
     */
    function forum_current_group_id()
    {
        global $member_id, $is_logged;

        if (empty($is_logged) || empty($member_id["user_group"])) {
            return 5;
        }

        return intval($member_id["user_group"]);
    }
}

if (!function_exists("forum_cat_access_allowed")) {
    /**
     * Verilen `permissions` değerine göre grup erişimini döndürür.
     *
     * @param string   $permissions Kategori `permissions` alanı
     * @param int|null $group_id    Kontrol edilecek grup (null: geçerli kullanıcı)
     * @return bool
     */
    function forum_cat_access_allowed($permissions, $group_id = null)
    {
        $group_id = $group_id === null ? forum_current_group_id() : intval($group_id);

        // Yönetici hiçbir zaman kilitlenmez.
        if ($group_id === 1) {
            return true;
        }

        $permissions = strtolower(trim((string) $permissions));
        if ($permissions === "" || $permissions === "all") {
            return true;
        }

        $allowed = array_filter(array_map("trim", explode(",", $permissions)), "strlen");

        return in_array((string) $group_id, $allowed, true);
    }
}

if (!function_exists("forum_cat_access_where")) {
    /**
     * SQL WHERE parçası üretir. Sorguda `_forum_cats` tablosunun
     * verilen alias ile JOIN edilmiş olması gerekir.
     *
     * @param string   $cat_alias Kategori tablosunun alias'ı
     * @param int|null $group_id  Kontrol edilecek grup (null: geçerli kullanıcı)
     * @return string
     */
    function forum_cat_access_where($cat_alias = "c", $group_id = null)
    {
        $group_id = $group_id === null ? forum_current_group_id() : intval($group_id);

        if ($group_id === 1) {
            return "1=1";
        }

        $alias = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $cat_alias);
        if ($alias === "") {
            $alias = "c";
        }

        return "(" .
            "LOWER(TRIM({$alias}.permissions)) IN ('', 'all')" .
            " OR FIND_IN_SET('{$group_id}', REPLACE({$alias}.permissions, ' ', ''))" .
            ")";
    }
}

if (!function_exists("forum_category_access_allowed")) {
    /**
     * Kategori kimliğine göre erişim kontrolü. Kategori yoksa erişim yoktur.
     *
     * @param int      $cat_id
     * @param int|null $group_id
     * @return bool
     */
    function forum_category_access_allowed($cat_id, $group_id = null)
    {
        global $db;

        $cat_id = intval($cat_id);
        if ($cat_id <= 0) {
            return false;
        }

        $row = $db->super_query(
            "SELECT permissions FROM " . PREFIX . "_forum_cats WHERE id = '{$cat_id}'",
        );

        if (!$row || !isset($row["permissions"])) {
            return false;
        }

        return forum_cat_access_allowed($row["permissions"], $group_id);
    }
}
