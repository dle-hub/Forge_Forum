<?php
/*
=====================================================
 Forge Forum Engine — CAPTCHA / Anti-Spam Yardımcıları
-----------------------------------------------------
 DLE native captcha altyapısını (resim captcha = antibot.php,
 reCAPTCHA/hCaptcha/Turnstile/Yandex ailesi = ReCaptcha sınıfı)
 forum konu/cevap formları için sarar.
=====================================================
*/
if (!defined("DATALIFEENGINE")) {
    die("Hacking attempt!");
}

if (!function_exists("forum_captcha_group_on")) {
    /**
     * DLE grup izni: bu grup captcha çözmek zorunda mı?
     * (Üye Grupları -> "Resim doğrulaması" => captcha)
     */
    function forum_captcha_group_on($group_id)
    {
        global $user_group;
        $g = intval($group_id);
        return !empty($user_group[$g]["captcha"]);
    }
}

if (!function_exists("forum_captcha_active")) {
    /**
     * Forum captcha'sı bu bağlamda aktif mi?
     * @param string $context 'topic' (yeni konu) | 'reply' (cevap)
     */
    function forum_captcha_active($context = "reply")
    {
        global $forum_cfg, $member_id;

        $key = $context === "topic" ? "enable_captcha_topic" : "enable_captcha_reply";
        if (empty($forum_cfg[$key])) {
            return false;
        }

        // Sadece captcha zorunlu gruplarda göster (DLE native grup izni)
        $group = isset($member_id["user_group"]) ? intval($member_id["user_group"]) : 5;
        if (!forum_captcha_group_on($group)) {
            return false;
        }

        return true;
    }
}

if (!function_exists("forum_captcha_html")) {
    /**
     * Captcha alanlarının HTML'ini döndürür (resim captcha veya reCAPTCHA ailesi).
     */
    function forum_captcha_html()
    {
        global $config, $lang;

        $reload = htmlspecialchars($lang["reload_code"] ?? "Kodu yenile", ENT_QUOTES, "UTF-8");

        if (empty($config["allow_recaptcha"])) {
            // ---- Resim captcha (antibot.php) ----
            $path = parse_url($config["http_home_url"]);
            $base = isset($path["path"]) && $path["path"] !== "" ? $path["path"] : "/";
            $src = $base . "index.php?controller=antibot";

            return '<div class="forum-captcha forum-captcha--image">'
                . '<a href="#" onclick="forumReloadCaptcha(); return false;" title="' . $reload . '">'
                . '<span id="forum-captcha-img"><img src="' . htmlspecialchars($src, ENT_QUOTES, "UTF-8") . '" alt="' . $reload . '" width="160" height="80"></span>'
                . "</a>"
                . '<input type="text" name="sec_code" class="forum-captcha__input" required autocomplete="off" placeholder="' . $reload . '">'
                . "</div>";
        }

        // ---- reCAPTCHA ailesi ----
        $type = intval($config["allow_recaptcha"]);
        $lc = htmlspecialchars($lang["language_code"] ?? "tr", ENT_QUOTES, "UTF-8");
        $pub = htmlspecialchars($config["recaptcha_public_key"] ?? "", ENT_QUOTES, "UTF-8");
        $theme = htmlspecialchars($config["recaptcha_theme"] ?? "light", ENT_QUOTES, "UTF-8");

        if ($type === 2) {
            // Google reCAPTCHA v3 (görünmez)
            return '<input type="hidden" name="g-recaptcha-response" id="forum-g-recaptcha-response" value="">'
                . '<script src="https://www.google.com/recaptcha/api.js?render=' . $pub . '"></script>';
        }

        $name = "g-recaptcha";
        $url = "https://www.google.com/recaptcha/api.js?hl=" . $lc;
        if ($type === 3) {
            $name = "h-captcha";
            $url = "https://js.hcaptcha.com/1/api.js?hl=" . $lc;
        } elseif ($type === 4) {
            $name = "cf-turnstile";
            $url = "https://challenges.cloudflare.com/turnstile/v0/api.js?compat=recaptcha";
        } elseif ($type === 5) {
            $name = "smart-captcha";
            $url = "https://smartcaptcha.cloud.yandex.ru/captcha.js";
        }

        return '<div class="forum-captcha">'
            . '<div class="' . $name . '" data-sitekey="' . $pub . '" data-theme="' . $theme . '" data-language="' . $lc . '"></div>'
            . '<script src="' . $url . '" async defer></script>'
            . "</div>";
    }
}

if (!function_exists("forum_captcha_check")) {
    /**
     * Gönderilen captcha doğru mu? (DLE native doğrulama mantığı)
     */
    function forum_captcha_check()
    {
        global $config;

        $ip = isset($_IP)
            ? $_IP
            : (isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : "");

        if (empty($config["allow_recaptcha"])) {
            // Resim captcha: antibot.php $_SESSION['sec_code_session'] üretir
            $code = isset($_POST["sec_code"]) ? trim((string) $_POST["sec_code"]) : "";
            $sess = isset($_SESSION["sec_code_session"]) ? (string) $_SESSION["sec_code_session"] : "";
            return $sess !== "" && $code !== "" && $code === $sess;
        }

        // reCAPTCHA ailesi
        $response = "";
        if (!empty($_POST["g_recaptcha_response"])) {
            $response = (string) $_POST["g_recaptcha_response"];
        } elseif (!empty($_POST["g-recaptcha-response"])) {
            $response = (string) $_POST["g-recaptcha-response"];
        } elseif (!empty($_POST["cf-turnstile-response"])) {
            $response = (string) $_POST["cf-turnstile-response"];
        } elseif (!empty($_POST["smart-token"])) {
            $response = (string) $_POST["smart-token"];
        }

        if ($response === "") {
            return false;
        }

        if (!class_exists("ReCaptcha")) {
            require_once ENGINE_DIR . "/classes/recaptcha.php";
        }
        if (!class_exists("ReCaptcha")) {
            return false;
        }

        $reCaptcha = new ReCaptcha($config["recaptcha_private_key"] ?? "");
        $resp = $reCaptcha->verifyResponse($ip, $response);

        return $resp !== null && !empty($resp->success);
    }
}
