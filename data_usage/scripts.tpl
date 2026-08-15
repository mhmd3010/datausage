{include file="sections/header.tpl"}

<style>
table th {
    text-transform: none !important;
    font-weight: 600 !important;
}
</style>

<!-- Top Navigation & Action Toolbar -->
<div class="row" style="margin-bottom: 20px;">
    <div class="col-sm-12" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="javascript:history.back()" class="btn btn-sm btn-default" style="font-weight: 600; border-radius: 8px;">
                <i class="fa fa-arrow-left"></i> Back
            </a>
            <h3 style="margin: 0; font-size: 20px; font-weight: 700; color: #1e293b;">
                <i class="fa fa-cogs text-warning"></i> Settings
            </h3>
        </div>
        <div class="btn-group">
            <a class="btn btn-primary btn-xs" href="{$_url}plugin/data_usage_admin">
                <i class="fa fa-dashboard"></i> Dashboard
            </a>
            <a class="btn btn-warning btn-xs active" href="{$_url}plugin/data_usage_scripts">
                <i class="fa fa-cogs"></i> Settings
            </a>
        </div>
    </div>
</div>

<!-- Main Panel -->
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">
                Settings & MikroTik Configuration
            </div>
            <div class="panel-body">
                
                <!-- Router Selector & Webhook URL -->
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Target Router:</label>
                            <select id="routerSelect" class="form-control" onchange="window.location.href='{$_url}plugin/data_usage_scripts&router_id=' + this.value;">
                                {foreach $routers as $r}
                                <option value="{$r->id}" {if $selectedRouterId == $r->id}selected{/if}>
                                    {$r->name} ({$r->ip_address}) [ID: {$r->id}]
                                </option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Webhook URL:</label>
                            <div class="input-group">
                                <input type="text" id="txtWebhookUrl" class="form-control" value="{$webhookUrl}" readonly style="font-family:monospace;">
                                <span class="input-group-btn">
                                    <button class="btn btn-default" type="button" onclick="copyText('txtWebhookUrl')">
                                        <i class="fa fa-copy"></i> Copy
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 1. Automatic MikroTik Poller Installation -->
                <div class="panel panel-default">
                    <div class="panel-heading" style="display:flex; justify-content:space-between; align-items:center; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <strong>1. MikroTik Poller (Auto-Install)</strong>
                        </div>
                        <div class="btn-group">
                            <form method="POST" action="{$_url}plugin/data_usage_install_poller" style="display:inline-block; margin-right: 5px;">
                                <input type="hidden" name="router_id" value="{$selectedRouterId}">
                                <button type="submit" class="btn btn-xs btn-success">
                                    <i class="fa fa-magic"></i> Auto-Install / Update on Target Router
                                </button>
                            </form>
                            <a href="{$_url}plugin/data_usage_install_poller&router_id=0" class="btn btn-xs btn-primary" onclick="return confirm('Install / update poller script on all enabled routers?')">
                                <i class="fa fa-sitemap"></i> Install on All Routers
                            </a>
                        </div>
                    </div>
                    <div class="panel-body" style="padding: 10px 15px;">
                        <small class="text-muted">
                            <i class="fa fa-check-circle text-success"></i> Auto-installs <strong>dataUsagePoller</strong> and a 2-minute scheduler on MikroTik via API.
                        </small>
                    </div>
                </div>

                <!-- 2. PPP & Hotspot Profile Scripts -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <strong>2. PPPoE Profile Scripts</strong>
                            </div>
                            <div class="panel-body">
                                <div class="form-group">
                                    <label>On Up Script:</label>
                                    <textarea id="scriptPppUp" class="form-control" rows="7" readonly style="font-family: monospace; font-size: 11px; background: #272822; color: #f8f8f2;">
:local routerId "{$selectedRouterId}";
:local serverUrl "{$webhookUrl}";
:local sid [/ppp active get [find name=$user] session-id];
:local iface [/interface find where name=$interface];
:local linkUp [/interface get $iface last-link-up-time];
{literal}:if ($sid = "") do={ :set sid $user; }{/literal}
:log info ("dataUsage [PPPoE on-up]: User " . $user . " connected (IP: " . $address . ", MAC: " . $caller-id . ", SID: " . $sid . ")");
/tool fetch url=$serverUrl http-method=post http-data="action=session-start&router=$routerId&service=PPPoE&username=$user&session_id=$sid&mac=$caller-id&ip=$address&start_time=$linkUp" keep-result=no;</textarea>
                                </div>
                                <div class="form-group">
                                    <label>On Down Script:</label>
                                    <textarea id="scriptPppDown" class="form-control" rows="6" readonly style="font-family: monospace; font-size: 11px; background: #272822; color: #f8f8f2;">
:local routerId "{$selectedRouterId}";
:local serverUrl "{$webhookUrl}";
:local secret [/ppp secret find where name=$user];
:local loggedOut [/ppp secret get $secret last-logged-out];
:local discReason [/ppp secret get $secret last-disconnect-reason];
:log info ("dataUsage [PPPoE on-down]: User " . $user . " disconnected (" . $discReason . ")");
/tool fetch url=$serverUrl http-method=post http-data="action=session-end&router=$routerId&service=PPPoE&username=$user&closed_time=$loggedOut&close_reason=$discReason" keep-result=no;</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <strong>3. Hotspot Profile Scripts</strong>
                            </div>
                            <div class="panel-body">
                                <div class="form-group">
                                    <label>On Login Script:</label>
                                    <textarea id="scriptHsLogin" class="form-control" rows="6" readonly style="font-family: monospace; font-size: 11px; background: #272822; color: #f8f8f2;">
:local routerId "{$selectedRouterId}";
:local serverUrl "{$webhookUrl}";
:log info ("dataUsage [Hotspot on-login]: User " . $user . " logged in (IP: " . $address . ", MAC: " . $"mac-address" . ")");
/tool fetch url=$serverUrl http-method=post http-data="action=session-start&router=$routerId&service=Hotspot&username=$user&mac=$"mac-address"&ip=$address" keep-result=no;</textarea>
                                </div>
                                <div class="form-group">
                                    <label>On Logout Script:</label>
                                    <textarea id="scriptHsLogout" class="form-control" rows="6" readonly style="font-family: monospace; font-size: 11px; background: #272822; color: #f8f8f2;">
:local routerId "{$selectedRouterId}";
:local serverUrl "{$webhookUrl}";
:log info ("dataUsage [Hotspot on-logout]: User " . $user . " logged out (Cause: " . $cause . ")");
/tool fetch url=$serverUrl http-method=post http-data="action=session-end&router=$routerId&service=Hotspot&username=$user&mac=$"mac-address"&ip=$address&uptime_secs=$"uptime-secs"&bytes_in=$"bytes-in"&bytes_out=$"bytes-out"&close_reason=$cause" keep-result=no;</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Linux Server Crontab Setup -->
                <div class="panel panel-default">
                    <div class="panel-heading" style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <strong>4. Server Crontab (Auto-Sync)</strong>
                            <span style="font-size: 11px; margin-left: 10px;">
                                {if $cronInfo['status'] == 'active'}
                                    <span class="text-success" style="font-weight: 600;"><i class="fa fa-check-circle"></i> Last run: {$cronInfo['last_run']} ({$cronInfo['ago']})</span>
                                {elseif $cronInfo['status'] == 'delayed'}
                                    <span class="text-warning" style="font-weight: 600;"><i class="fa fa-clock-o"></i> Last run: {$cronInfo['last_run']} ({$cronInfo['ago']})</span>
                                {else}
                                    <span class="text-danger" style="font-weight: 600;"><i class="fa fa-exclamation-triangle"></i> Cron setup needed</span>
                                {/if}
                            </span>
                        </div>
                        <button class="btn btn-xs btn-default" onclick="copyText('txtCronCmd')">
                            <i class="fa fa-copy"></i> Copy
                        </button>
                    </div>
                    <div class="panel-body" style="padding: 10px 15px;">
                        <div class="input-group">
                            <input type="text" id="txtCronCmd" class="form-control input-sm" value="*/5 * * * * php /var/www/html/demo/system/plugin/ui/data_usage/cron_sync.php >/dev/null 2>&1" readonly style="font-family: monospace; font-size: 11px; background: #272822; color: #f8f8f2;">
                            <span class="input-group-btn">
                                <button class="btn btn-default btn-sm" type="button" onclick="copyText('txtCronCmd')">
                                    <i class="fa fa-copy"></i> Copy
                                </button>
                            </span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
function updateRouterId(val) {
    var textareas = document.querySelectorAll('textarea');
    textareas.forEach(function(el) {
        el.value = el.value.replace(/:local routerId "[^"]*";/g, ':local routerId "' + val + '";');
        el.value = el.value.replace(/:local routerId \\"[^"]*\\";/g, ':local routerId \\"' + val + '\\";');
    });
}

function copyText(elementId) {
    var copyText = document.getElementById(elementId);
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    document.execCommand("copy");
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            position: 'top-end',
            icon: 'success',
            title: 'Copied to clipboard!',
            showConfirmButton: false,
            timer: 1500
        });
    }
}

// ── Developer Popups & Easter Egg ──
function initDeveloperPopups() {
    var triggerEgg = function() {
        Swal.fire({
            title: 'Data Usage & Tracker',
            html: '<div style="text-align:center; padding:10px;">' +
                  '<i class="fa fa-code fa-2x" style="color:#3c8dbc; margin-bottom:10px;"></i>' +
                  '<p style="font-size:14px; margin-bottom:4px;">Developed by <b>Mahmud</b></p>' +
                  '<p style="font-size:12px; color:#64748b; margin-bottom:16px;">Commercial Edition for SysTik</p>' +
                  '<a href="https://wa.me/+8801540221898?text=I%20want%20to%20help%20you%20buy%20a%20meal" target="_blank" class="btn btn-success btn-sm" style="border-radius:20px; padding:6px 16px;">' +
                  '<i class="fa fa-whatsapp"></i> Donate / Buy a Meal ☕' +
                  '</a>' +
                  '</div>',
            showConfirmButton: true,
            confirmButtonText: 'Close'
        });
    };

    var titles = document.querySelectorAll('h3, .panel-heading');
    titles.forEach(function(el) {
        if (el.innerText.toLowerCase().indexOf('settings') !== -1 || el.innerText.toLowerCase().indexOf('script') !== -1) {
            el.style.cursor = 'pointer';
            el.title = "Developer Info";
            el.addEventListener('click', triggerEgg);
        }
    });
}
document.addEventListener('DOMContentLoaded', initDeveloperPopups);
</script>

{include file="sections/footer.tpl"}
