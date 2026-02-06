<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<style>
    .mainCard {
        margin-top: 110px;
        text-align: center;
    }

    .bf-logo svg {
        margin-bottom: 0;
        width: 80px;
        height: auto;
    }

    .bf-logo p {
        margin: 0 0 5px 0;
        font-size: 20px;
        color: #46596b;
        font-family: 'Roboto';
        font-weight: 600;
    }

    .bf-logo div {
        margin: 0 0 30px 0;
        display: inline-block;
    }

    .bf-logo div a {
        color: #707b83;
        text-decoration: none;
        font-size: 14px;

    }

    .bf-logo div a:focus-visible {
        color: red;
        border: 1px solid #000;
        padding: 3px;
    }

    .bf-logo div a:focus {
        box-shadow: none;
    }

    .bf-logo div a:hover {
        color: #6518b6;
    }

    .bf-logo div span {
        margin: 0 2px;
        color: #707b83;
    }

    .errorMsg {
        width: 40%;
        padding: 40px 50px;
        display: flex;
        flex-direction: column;
        align-items: center;
        margin: 30px auto 0 auto;
        background-color: #fff;
        border-radius: 5px;
        box-shadow: 0px 3px 10px 1px rgb(0 0 0 / 5%);
    }

    .errorMsg svg {
        color: #6518b6;
        width: 80px;
        height: auto;
    }

    .errorMsg p {
        font-size: 18px;
        font-family: 'Roboto';
        color: #3b4e5d;
        margin-bottom: 0;
    }

    .successMsg svg {
        color: green;
    }

    .formField {
        margin-top: 20px;
    }

    .formField form {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .formField form p {
        font-weight: 600;
        margin: 0 8px 0 0;
        color: #2c3b47;
    }

    .formField form input {
        cursor: pointer;
        background-color: #6518b6;
        border: none;
        border-radius: 100px;
        padding: 8px 16px;
        color: #fff;
        box-shadow: 0px 3px 10px 1px rgb(0 0 0 / 5%);
        color: #fff;
        transition: 0.3s all ease;
    }

    .formField form input:hover {
        box-shadow: 0px 3px 10px 1px rgb(0 0 0 / 15%);
    }

    .backBtn {
        text-align: center;
        margin-top: 25px;
    }

    .btn2 {
        display: inline-flex;
        text-decoration: none;
        align-items: center;
        font-size: 14px;
        background-color: #03a9f4;
        color: #fff;
        padding: 5px 15px;
        border-radius: 100px;
        font-weight: 600;
        box-shadow: 0px 3px 10px 1px rgb(0 0 0 / 5%);
    }

    .btn2 svg {
        width: 20px;
        margin-right: 5px;
    }

    .btn2:hover {
        color: #fff;
        box-shadow: 0px 3px 10px 1px rgb(0 0 0 / 15%);
    }

    .footerBtn {
        margin-top: 60px;
        text-align: center;
    }

    .footerBtn a {
        font-weight: 400;
        padding: 8px 16px;
        text-decoration: none;
        border-radius: 100px;
        margin-right: 5px;
        transition: 0.3s all ease;
    }

    .subscribeBtn {
        border: 0.15em solid #6518b6;
        color: #6518b6;
        background-color: rgb(255 255 255 / 50%)
    }

    .subscribeBtn:hover {
        color: #6518b6;
        box-shadow: 0px 3px 10px 1px rgb(0 0 0 / 15%);
    }

    .homeBtn {
        background-color: #0f1923;
        color: #fff;
        border: 0.15em solid #0f1923;
    }

    .homeBtn:hover {
        color: #fff;
        box-shadow: 0px 3px 10px 1px rgb(0 0 0 / 15%);
    }

    .supportLink {
        margin-top: 18px;
    }

    .supportLink a {
        display: inline-block;
    }

    .supportLink a:hover svg {
        color: #6518b6;
    }

    .supportLink a:focus-visible {
        border: 1px solid #000;
        /* padding: 5px; */
    }

    .supportLink a:focus {
        box-shadow: none;
    }

    .supportLink a svg {
        color: #92a5b3;
        width: 20px;
        height: auto;
        margin-right: 10px;
        transition: 0.3s all ease;
    }
</style>


<div class="mainCard">
    <div class="bf-logo">
        <svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 600 600">
            <g id="Layer_1-2" data-name="Layer 1">
                <image class="cls-1" width="97" height="97" isolation="isolate" opacity="0.39" transform="translate(-7 -7) scale(6.33)" xlink:href="data:image/png;base64,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" />
                <rect class="cls-2" x="29.4" y="30.09" width="529.62" height="529.62" rx="127.48" fill="#ffffff" />
                <rect class="cls-3" x="383.87" y="212.46" width="38.17" height="23.93" fill="#8f61ff" />
                <polygon class="cls-3" points="383.87 437.74 422.1 437.74 422.1 256.51 383.87 256.51 383.87 272.53 383.87 307.22 383.87 437.74" fill="#8f61ff" />
                <path class="cls-4" fill="#6518b6" d="M402.54,152.13H221.26q-50.64,0-50.64,51.72v54.88q0,24.69,27.47,30.26v2q-31.65,8.73-31.65,32.6V395q0,42.66,43.36,42.6H347.47v-34.5H219.74c-10.07,0-15.13-4.11-15.13-12.66v-63.3q0-20.19,22.72-20.25h156.6V272.15H230.5q-22,0-21.9-20.7V207.14q0-20.7,17.09-20.7H421.91V151.75Z" />
            </g>
        </svg>
        <p>Bit Integrations</p>
        <div>
            <a href="https://docs.bit-integrations.bitapps.pro/" tabindex="1" target="_blank">Docs</a>
            <span>•</span>
            <a href="https://tawk.to/chat/60eac4b6d6e7610a49aab375/1faah0r3e" tabindex="2" target="_blank">Support</a>
        </div>
    </div>

    <?php
    if (isset($_POST) && isset($_POST['disconnect'])) {
        include_once BTCBI_PRO_PLUGIN_DIR_PATH . 'includes/Core/Update/API.php';
        $activationStatus = BitCode\FI\Core\Update\API::disconnectLicense();
        if ($activationStatus === true) { ?>
            <div class="errorMsg">
                <svg width="16px" height="16px" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M13.617 3.844a2.87 2.87 0 0 0-.451-.868l1.354-1.36L13.904 1l-1.36 1.354a2.877 2.877 0 0 0-.868-.452 3.073 3.073 0 0 0-2.14.075 3.03 3.03 0 0 0-.991.664L7 4.192l4.327 4.328 1.552-1.545c.287-.287.508-.618.663-.992a3.074 3.074 0 0 0 .075-2.14zm-.889 1.804a2.15 2.15 0 0 1-.471.705l-.93.93-3.09-3.09.93-.93a2.15 2.15 0 0 1 .704-.472 2.134 2.134 0 0 1 1.689.007c.264.114.494.271.69.472.2.195.358.426.472.69a2.134 2.134 0 0 1 .007 1.688zm-4.824 4.994l1.484-1.545-.616-.622-1.49 1.551-1.86-1.859 1.491-1.552L6.291 6 4.808 7.545l-.616-.615-1.551 1.545a3 3 0 0 0-.663.998 3.023 3.023 0 0 0-.233 1.169c0 .332.05.656.15.97.105.31.258.597.459.862L1 13.834l.615.615 1.36-1.353c.265.2.552.353.862.458.314.1.638.15.97.15.406 0 .796-.077 1.17-.232.378-.155.71-.376.998-.663l1.545-1.552-.616-.615zm-2.262 2.023a2.16 2.16 0 0 1-.834.164c-.301 0-.586-.057-.855-.17a2.278 2.278 0 0 1-.697-.466 2.28 2.28 0 0 1-.465-.697 2.167 2.167 0 0 1-.17-.854 2.16 2.16 0 0 1 .642-1.545l.93-.93 3.09 3.09-.93.93a2.22 2.22 0 0 1-.711.478z" />
                </svg>
                <p>License Disconnected Successfully</p>
            </div>
        <?php
        } else { ?>
            <div class="errorMsg">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-alert-triangle">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                <p><?php echo $activationStatus; ?></p>
            </div>
            <div class="formField">
                <form action="" method="post">
                    <p>Disconnect this site from license? </p>
                    <input type="submit" name="disconnect" value="Disconnect">
                </form>
            </div>
            <?php
        }
    } else {
        if (!empty($integrateStatus['expireIn'])) {
            $expireInDays = (strtotime($integrateStatus['expireIn']) - time()) / DAY_IN_SECONDS;
            if ($expireInDays < 25) {
                $notice = $expireInDays > 0 ?
                    sprintf(__('Bit Integrations Pro License will expire in %s days', 'bit-integrations'), (int) $expireInDays)
                    : __('Bit Integrations Pro License is expired', 'bit-integrations')
            ?>
                <div class="errorMsg">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-alert-triangle">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    <p><?php echo $notice; ?></p>
                </div>
        <?php
            }
        } ?>
        <div class="formField">
            <form action="" method="post">
                <p>Disconnect this site from license? </p>
                <input type="submit" name="disconnect" value="Disconnect">
            </form>
        </div>
    <?php
    }
    ?>

    <div class="supportLink">
        <a href="mailto:support@bitapps.pro" tabindex="6" target="_blank">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-mail">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
            </svg>
        </a>
        <a href="https://www.bitapps.pro/bit-integrations" tabindex="7" target="_blank">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-globe">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="2" y1="12" x2="22" y2="12"></line>
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
            </svg>
        </a>
        <a href="https://www.facebook.com/groups/bitcommunityusers" tabindex="8" target="_blank">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-facebook">
                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
            </svg>
        </a>
        <a href="https://www.youtube.com/channel/UCjUl8UGn-G6zXZ-Wpd7Sc3g/featured" tabindex="9" target="_blank">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-youtube">
                <path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path>
                <polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon>
            </svg>
        </a>
    </div>
</div>

<div class="footerBtn">
    <a href="https://subscription.bitapps.pro/wp/login" class="subscribeBtn">Go to Subscription</a>
    <a href="<?= get_admin_url() ?>admin.php?page=bit-integrations#/" class="homeBtn">Go Bit Integrations Dashboard</a>
</div>