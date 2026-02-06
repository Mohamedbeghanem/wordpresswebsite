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

    .formCard {
        width: 40%;
        margin: 0 auto;
        background-color: #fff;
        padding: 7px 7px 7px 15px;
        border-radius: 100px;
        box-shadow: 0px 3px 10px 1px rgb(0 0 0 / 5%);
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

    .myBtn {
        background-color: #6518b6;
        border: none;
        cursor: pointer;
        padding: 10px 20px;
        color: #fff;
        border-radius: 100px;
    }

    .formCard form {
        display: flex;
        justify-content: space-between;
    }

    .inputControl {
        width: 100%;
        border: none !important;
    }

    .inputControl:focus {
        box-shadow: none !important;
    }

    .errorMsg {
        color: red;
        margin-top: 14px;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .errorMsg svg {
        margin-right: 10px;
    }

    .successMsg {
        width: 40%;
        padding: 50px;
        display: flex;
        flex-direction: column;
        align-items: center;
        margin: 140px auto 0 auto;
        background-color: #fff;
        border-radius: 5px;
        box-shadow: 0px 3px 10px 1px rgb(0 0 0 / 5%);
    }

    .successMsg svg {
        color: green;
        width: 80px;
        height: auto;
    }

    .successMsg p {
        font-size: 18px;
        font-family: 'Roboto';
        color: #3b4e5d;
        margin-bottom: 0;
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

    .autoActivateBtn {
        background-color: #6518b6;
        padding: 13px 20px;
        color: #fff;
        border-radius: 12px;
        text-decoration: none;
        margin-bottom: 12px;
        display: inline-block;
        transition: box-shadow 0.3s ease !important;
        font-weight: 500;
        font-size: 16px;
        box-shadow: 1px 2px 3px 0px #a9a9a9, 1px 5px 10px 0px #00000017;
        display: flex;
        align-items: center;
        margin-left: auto;
        margin-right: auto;
        width: fit-content;
    }

    .autoActivateBtn svg {
        margin-right: 5px;
    }

    .autoActivateBtn:hover {
        color: #fff;
        box-shadow: 0px 3px 10px 1px rgb(0 0 0 / 20%);
    }

    .orDivider {
        color: #707b83;
        text-decoration: none;
        font-size: 15px;
        margin-bottom: 13px;
        display: inline-block;
    }
</style>

<?php
function get_current_admin_url()
{
    return admin_url(sprintf(basename($_SERVER['REQUEST_URI'])));
}

$licenseKey = '';
$checkForLicense = false;

if (isset($_GET['licenseKey'])) {
    $licenseKey = $_GET['licenseKey'];
    $checkForLicense = true;
} elseif (isset($_POST) && isset($_POST['licenseKey'])) {
    $licenseKey = $_POST['licenseKey'];
    $checkForLicense = true;
}

$getStatus = false;
$getErrorMsg;

function activateLicenseKey($licenseKey)
{
    include_once BTCBI_PRO_PLUGIN_DIR_PATH . 'includes/Core/Update/API.php';
    $activationStatus = BitCode\FI\Core\Update\API::activateLicense($licenseKey);
    $data = [];
    if ($activationStatus === true) {
        $data['status'] = true;
        $data['message'] = '';
    } else {
        $data['status'] = false;
        $data['message'] = $activationStatus;
    }

    return $data;
}

if (!empty($licenseKey)) {
    $status = activateLicenseKey($licenseKey);
    $getStatus = $status['status'];
    $getErrorMsg = $status['message'];
}
?>

<?php if ($getStatus) : ?>
    <div class="successMsg">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-check-circle">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <p>License Key Activated Successfully</p>
    </div>
<?php endif; ?>

<?php if (!$getStatus) : ?>
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
        <div>
            <a href="https://subscription.bitapps.pro/wp/activateLicense/?slug=bit-integrations-pro&redirect=<?php echo get_current_admin_url() ?>" class="autoActivateBtn" tabindex="3">
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:svgjs="http://svgjs.com/svgjs" version="1.1" width="20" height="20" x="0" y="0" viewBox="0 0 16.933333 16.933334" style="enable-background:new 0 0 512 512" xml:space="preserve" class="">
                    <g>
                        <g xmlns="http://www.w3.org/2000/svg" id="layer1" transform="translate(0 -280.067)">
                            <g fill="#33658a">
                                <path id="path17087" d="m2.9871068 283.42851 1.5878911 1.58789c.25.25.6249998-.125.375-.375l-1.587891-1.58789c-.04977-.0512-.118087-.08-.1894531-.0801-.238968-.002-.357568.28919-.185547.45508z" font-variant-ligatures="normal" font-variant-position="normal" font-variant-caps="normal" font-variant-numeric="normal" font-variant-alternates="normal" font-feature-settings="normal" text-indent="0" text-align="start" text-decoration-line="none" text-decoration-style="solid" text-decoration-color="#000000" text-transform="none" text-orientation="mixed" white-space="normal" shape-padding="0" isolation="auto" mix-blend-mode="normal" solid-color="#000000" solid-opacity="1" vector-effect="none" fill="#ede6ff" data-original="#33658a" class="" />
                                <path id="path17089" d="m6.5847635 281.65116a.26460982.26460982 0 0 0 -.234375.26758v1.85156a.2646485.2646485 0 1 0 .529297 0v-1.85156a.26460982.26460982 0 0 0 -.294922-.26758z" font-variant-ligatures="normal" font-variant-position="normal" font-variant-caps="normal" font-variant-numeric="normal" font-variant-alternates="normal" font-feature-settings="normal" text-indent="0" text-align="start" text-decoration-line="none" text-decoration-style="solid" text-decoration-color="#000000" text-transform="none" text-orientation="mixed" white-space="normal" shape-padding="0" isolation="auto" mix-blend-mode="normal" solid-color="#000000" solid-opacity="1" vector-effect="none" fill="#ede6ff" data-original="#33658a" class="" />
                                <path id="path17091" d="m1.8523378 286.94609h1.851562c.3528651 0 .3528651-.5293 0-.5293h-1.865232c-.366206.0185-.338866.54782.01367.5293z" font-variant-ligatures="normal" font-variant-position="normal" font-variant-caps="normal" font-variant-numeric="normal" font-variant-alternates="normal" font-feature-settings="normal" text-indent="0" text-align="start" text-decoration-line="none" text-decoration-style="solid" text-decoration-color="#000000" text-transform="none" text-orientation="mixed" white-space="normal" shape-padding="0" isolation="auto" mix-blend-mode="normal" solid-color="#000000" solid-opacity="1" vector-effect="none" fill="#ede6ff" data-original="#33658a" class="" />
                                <path id="path17093" d="m11.983201 292.4246 1.587891 1.58789c.25.25.625-.125.375-.375l-1.587891-1.58789c-.04977-.0512-.118087-.08-.189453-.0801-.238968-.002-.357568.2892-.185547.45508z" font-variant-ligatures="normal" font-variant-position="normal" font-variant-caps="normal" font-variant-numeric="normal" font-variant-alternates="normal" font-feature-settings="normal" text-indent="0" text-align="start" text-decoration-line="none" text-decoration-style="solid" text-decoration-color="#000000" text-transform="none" text-orientation="mixed" white-space="normal" shape-padding="0" isolation="auto" mix-blend-mode="normal" solid-color="#000000" solid-opacity="1" vector-effect="none" fill="#ede6ff" data-original="#33658a" class="" />
                                <path id="path17095" d="m10.287889 293.02812a.26460982.26460982 0 0 0 -.234375.26757v1.85157a.264648.264648 0 1 0 .529296 0v-1.85157a.26460982.26460982 0 0 0 -.294921-.26757z" font-variant-ligatures="normal" font-variant-position="normal" font-variant-caps="normal" font-variant-numeric="normal" font-variant-alternates="normal" font-feature-settings="normal" text-indent="0" text-align="start" text-decoration-line="none" text-decoration-style="solid" text-decoration-color="#000000" text-transform="none" text-orientation="mixed" white-space="normal" shape-padding="0" isolation="auto" mix-blend-mode="normal" solid-color="#000000" solid-opacity="1" vector-effect="none" fill="#ede6ff" data-original="#33658a" class="" />
                                <path id="path17097" d="m13.229295 290.11991a.26465.26465 0 1 0 0 .5293h1.851562a.26465.26465 0 1 0 0-.5293z" font-variant-ligatures="normal" font-variant-position="normal" font-variant-caps="normal" font-variant-numeric="normal" font-variant-alternates="normal" font-feature-settings="normal" text-indent="0" text-align="start" text-decoration-line="none" text-decoration-style="solid" text-decoration-color="#000000" text-transform="none" text-orientation="mixed" white-space="normal" shape-padding="0" isolation="auto" mix-blend-mode="normal" solid-color="#000000" solid-opacity="1" vector-effect="none" fill="#ede6ff" data-original="#33658a" class="" />
                            </g>
                            <path id="path3135" d="m12.957865 280.59634c-.880547 0-1.762332.33865-2.435511 1.01183l-3.6896965 3.68556c-.282226.28205-.499748.60347-.663009.94154.472509-.22882.984906-.34722 1.498616-.34726.271838-.00002.542994.0362.808736.10025l3.2132405-3.21221c.352888-.35288.809135-.52709 1.267624-.52709.458491 0 .916805.17421 1.26969.52709.705779.70578.705779 1.82947 0 2.53525l-3.691765 3.68763c-.7060755.70563-1.8294645.70578-2.5352455 0-.399762-.39976-.568077-.93406-.514697-1.45107-.394697.0403-.779113.2119-1.085204.51779l-.4769755.47646c.1432851.59463.4439555 1.15967.9089895 1.62471 1.346332 1.34633 3.5243215 1.34583 4.8710215 0l3.691763-3.68763c1.346335-1.34634 1.346335-3.52469 0-4.87102-.673179-.67318-1.557028-1.01183-2.437577-1.01183z" fill="#d3c1ff" font-variant-ligatures="normal" font-variant-position="normal" font-variant-caps="normal" font-variant-numeric="normal" font-variant-alternates="normal" font-feature-settings="normal" text-indent="0" text-align="start" text-decoration-line="none" text-decoration-style="solid" text-decoration-color="#000000" text-transform="none" text-orientation="mixed" white-space="normal" shape-padding="0" isolation="auto" mix-blend-mode="normal" solid-color="#000000" solid-opacity="1" vector-effect="none" data-original="#33b9ef" class="" />
                            <path id="path3152" d="m3.9783939 296.4681c.8805458 0 1.7623309-.33865 2.4355096-1.01183l3.6896975-3.68556c.282226-.28205.499748-.60347.663009-.94154-.472509.22882-.9849065.34723-1.4986165.34726-.271838.00002-.542994-.0362-.808736-.10025l-3.2132398 3.21221c-.352888.35288-.8091358.52709-1.2676238.52709-.4584922 0-.9168061-.17421-1.2696909-.52709-.7057792-.70578-.7057792-1.82947 0-2.53525l3.6917655-3.68763c.706075-.70563 1.829464-.70578 2.535245 0 .399762.39976.568077.93406.514697 1.45107.394697-.0403.7791135-.2119 1.0852045-.51779l.476975-.47646c-.143285-.59463-.443955-1.15967-.908989-1.62471-1.3463325-1.34633-3.5243225-1.34583-4.8710222 0l-3.691763 3.68763c-1.34633486 1.34634-1.34633486 3.52469 0 4.87102.67318.67318 1.5570281 1.01183 2.4375781 1.01183z" fill="#ede6ff" font-variant-ligatures="normal" font-variant-position="normal" font-variant-caps="normal" font-variant-numeric="normal" font-variant-alternates="normal" font-feature-settings="normal" text-indent="0" text-align="start" text-decoration-line="none" text-decoration-style="solid" text-decoration-color="#000000" text-transform="none" text-orientation="mixed" white-space="normal" shape-padding="0" isolation="auto" mix-blend-mode="normal" solid-color="#000000" solid-opacity="1" vector-effect="none" data-original="#33658a" class="" />
                        </g>
                    </g>
                </svg>

                Connect with Bit Apps subscription
            </a>
        </div>
        <div><span class="orDivider">Or</span></div>
        <div class="formCard">
            <form action="" method="post">
                <input type="text" tabindex="4" name="licenseKey" class="inputControl" placeholder="Enter License Key here">
                <input type="submit" tabindex="5" value="Activate" class="myBtn">
            </form>
        </div>
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
        <?php if ($checkForLicense && empty($licenseKey)) : ?>
            <span class="errorMsg"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-alert-triangle">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg> License key is missing</span>
        <?php endif; ?>

        <?php if (isset($getErrorMsg) && !empty($getErrorMsg)) : ?>
            <span class="errorMsg"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-alert-triangle">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg><?= $getErrorMsg ?></span>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="footerBtn">
    <a href="https://subscription.bitapps.pro/wp/login" tabindex="10" class="subscribeBtn">Go to Subscription</a>
    <a href="<?= get_admin_url() ?>admin.php?page=bit-integrations#/" tabindex="11" class="homeBtn">Go to Bit Integrations Dashboard</a>
</div>