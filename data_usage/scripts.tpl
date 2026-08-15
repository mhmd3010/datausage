{include file="sections/header.tpl"}

<style>
table th {
    text-transform: none !important;
    font-weight: 600 !important;
}
.profile-table td, .profile-table th {
    vertical-align: middle !important;
}
</style>

<!-- Top Navigation & Action Toolbar -->
<div class="row" style="margin-bottom: 16px;">
    <div class="col-sm-12" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="javascript:history.back()" class="btn btn-sm btn-default" style="font-weight: 600; border-radius: 6px;">
                Back
            </a>
            <h3 style="margin: 0; font-size: 20px; font-weight: 700; color: #1e293b;">
                Settings &amp; MikroTik Setup
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

<!-- 1. Router & General Configuration Panel -->
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-primary" style="border-radius: 6px; margin-bottom: 18px;">
            <div class="panel-heading" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <span style="font-weight: 700;">Router &amp; Poller Configuration</span>
                <span class="label {if $routerOnline}label-success{else}label-danger{/if}" style="font-size: 11px;">
                    {if $routerOnline}Connected to Router{else}Router Unreachable{/if}
                </span>
            </div>
            <div class="panel-body" style="padding: 16px;">
                <!-- Main Config Row -->
                <div class="row" style="margin-bottom: 16px;">
                    <div class="col-md-3">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600; font-size: 12px; margin-bottom: 4px;">Target Router:</label>
                            <select id="routerSelect" class="form-control input-sm" style="font-size: 11px;" onchange="window.location.href='{$_url}plugin/data_usage_scripts&router_id=' + this.value;">
                                {foreach $routers as $r}
                                <option value="{$r->id}" {if $selectedRouterId == $r->id}selected{/if}>
                                    {$r->name} ({$r->ip_address})
                                </option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: 600; font-size: 12px; margin-bottom: 4px;">Webhook URL:</label>
                            <div class="input-group">
                                <input type="text" id="txtWebhookUrl" class="form-control input-sm" value="{$webhookUrl}" readonly style="font-family: monospace; font-size: 11px;">
                                <span class="input-group-btn">
                                    <button class="btn btn-default btn-sm" type="button" onclick="copyText('txtWebhookUrl')">
                                        Copy
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <form method="POST" action="{$_url}plugin/data_usage_scripts" style="margin: 0;">
                            <input type="hidden" name="csrf_token" value="{$csrf_token}">
                            <input type="hidden" name="save_traffic_settings" value="1">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-weight: 600; font-size: 12px; margin-bottom: 4px;">Traffic Polling Interval:</label>
                                <div class="input-group">
                                    <input type="number" name="traffic_poll_ms" class="form-control input-sm" value="{$trafficPollMs|default:100}" min="30" max="5000" step="10" required>
                                    <span class="input-group-addon" style="font-size: 11px; padding: 4px 8px;">ms</span>
                                    <span class="input-group-btn">
                                        <button type="submit" class="btn btn-primary btn-sm" style="font-weight: 600;">
                                            Save
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <hr style="margin: 12px 0 16px 0; border-color: #e2e8f0;">

                <!-- Poller & Crontab Row -->
                <div class="row">
                    <!-- Poller Action -->
                    <div class="col-md-5" style="margin-bottom: 10px;">
                        <label style="font-weight: 600; font-size: 12px; margin-bottom: 4px; display: block;">
                            MikroTik Live Poller Script:
                        </label>
                        <div class="btn-group">
                            <form method="POST" action="{$_url}plugin/data_usage_install_poller" style="display: inline-block; margin-right: 4px;">
                                <input type="hidden" name="csrf_token" value="{$csrf_token}">
                                <input type="hidden" name="router_id" value="{$selectedRouterId}">
                                <button type="submit" class="btn btn-sm btn-success" style="font-weight: 600;">
                                    Auto-Install on Target
                                </button>
                            </form>
                            <a href="{$_url}plugin/data_usage_install_poller&router_id=0" class="btn btn-sm btn-primary" onclick="return confirm('Install / update poller script on all enabled routers?')" style="font-weight: 600;">
                                Install on All
                            </a>
                        </div>
                    </div>

                    <!-- Server Crontab -->
                    <div class="col-md-7">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <label style="font-weight: 600; font-size: 12px; margin-bottom: 0;">
                                Server Cron Command:
                            </label>
                            <span style="font-size: 11px;">
                                {if $cronInfo['status'] == 'active'}
                                    <span class="text-success" style="font-weight: 600;">Active: {$cronInfo['ago']}</span>
                                {elseif $cronInfo['status'] == 'delayed'}
                                    <span class="text-warning" style="font-weight: 600;">Last: {$cronInfo['ago']}</span>
                                {else}
                                    <span class="text-danger" style="font-weight: 600;">Cron setup needed</span>
                                {/if}
                            </span>
                        </div>
                        <div class="input-group">
                            <input type="text" id="txtCronCmd" class="form-control input-sm" value="*/5 * * * * php /var/www/html/demo/system/plugin/ui/data_usage/cron_sync.php >/dev/null 2>&1" readonly style="font-family: monospace; font-size: 11px; background: #ffffff; color: #1e293b;">
                            <span class="input-group-btn">
                                <button class="btn btn-default btn-sm" type="button" onclick="copyText('txtCronCmd')">
                                    Copy
                                </button>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 2. Profile Script Automation Panel -->
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-default" style="border-radius: 6px; margin-bottom: 18px; border: 1px solid #e2e8f0;">
            <div class="panel-heading" style="background: #ffffff; border-bottom: 1px solid #e2e8f0; padding: 12px 16px;">
                <strong style="color: #1e293b; font-size: 14px;">Profile Automation (PPPoE &amp; Hotspot Webhooks)</strong>
            </div>
            <div class="panel-body" style="padding: 16px;">
                {if !$routerOnline}
                <div class="alert alert-warning" style="margin-bottom: 0; border-radius: 6px;">
                    Target router is offline. Please ensure the router is connected to manage profile webhooks.
                </div>
                {else}
                <form method="POST" action="{$_url}plugin/data_usage_manage_profile_scripts" id="profileScriptsForm">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <input type="hidden" name="router_id" value="{$selectedRouterId}">
                    <input type="hidden" name="action" id="profileActionInput" value="install">

                    <div class="row">
                        <!-- PPPoE Profiles Table -->
                        <div class="col-md-6" style="margin-bottom: 14px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <h5 style="margin: 0; font-weight: 700; color: #1e293b;">
                                    PPPoE Profiles ({count($pppProfiles)})
                                </h5>
                                <div class="btn-group btn-group-xs">
                                    <button type="button" class="btn btn-default btn-xs" onclick="toggleCheckboxes('chk_ppp_', true)">Select All</button>
                                    <button type="button" class="btn btn-default btn-xs" onclick="toggleCheckboxes('chk_ppp_', false)">None</button>
                                </div>
                            </div>
                            <div class="table-responsive" style="max-height: 220px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 4px;">
                                <table class="table table-striped table-hover profile-table" style="margin-bottom: 0;">
                                    <thead>
                                        <tr style="background: #f8fafc;">
                                            <th style="width: 40px; text-align: center;">#</th>
                                            <th>Profile Name</th>
                                            <th style="width: 110px; text-align: center;">Script Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {if count($pppProfiles) == 0}
                                        <tr><td colspan="3" class="text-center text-muted" style="padding: 12px;">No PPPoE profiles found</td></tr>
                                        {else}
                                        {foreach $pppProfiles as $idx => $p}
                                        <tr>
                                            <td style="text-align: center;">
                                                <input type="checkbox" name="ppp_profiles[]" value="{$p['name']}" class="chk_ppp_" id="ppp_p_{$idx}">
                                            </td>
                                            <td>
                                                <label for="ppp_p_{$idx}" style="cursor: pointer; margin-bottom: 0; font-weight: 600;">{$p['name']}</label>
                                            </td>
                                            <td style="text-align: center;">
                                                {if $p['installed']}
                                                <span class="label label-success">Installed</span>
                                                {else}
                                                <span class="label label-default">Not Installed</span>
                                                {/if}
                                            </td>
                                        </tr>
                                        {/foreach}
                                        {/if}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Hotspot User Profiles Table -->
                        <div class="col-md-6" style="margin-bottom: 14px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <h5 style="margin: 0; font-weight: 700; color: #1e293b;">
                                    Hotspot Profiles ({count($hsProfiles)})
                                </h5>
                                <div class="btn-group btn-group-xs">
                                    <button type="button" class="btn btn-default btn-xs" onclick="toggleCheckboxes('chk_hs_', true)">Select All</button>
                                    <button type="button" class="btn btn-default btn-xs" onclick="toggleCheckboxes('chk_hs_', false)">None</button>
                                </div>
                            </div>
                            <div class="table-responsive" style="max-height: 220px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 4px;">
                                <table class="table table-striped table-hover profile-table" style="margin-bottom: 0;">
                                    <thead>
                                        <tr style="background: #f8fafc;">
                                            <th style="width: 40px; text-align: center;">#</th>
                                            <th>Profile Name</th>
                                            <th style="width: 110px; text-align: center;">Script Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {if count($hsProfiles) == 0}
                                        <tr><td colspan="3" class="text-center text-muted" style="padding: 12px;">No Hotspot profiles found</td></tr>
                                        {else}
                                        {foreach $hsProfiles as $idx => $h}
                                        <tr>
                                            <td style="text-align: center;">
                                                <input type="checkbox" name="hs_profiles[]" value="{$h['name']}" class="chk_hs_" id="hs_p_{$idx}">
                                            </td>
                                            <td>
                                                <label for="hs_p_{$idx}" style="cursor: pointer; margin-bottom: 0; font-weight: 600;">{$h['name']}</label>
                                            </td>
                                            <td style="text-align: center;">
                                                {if $h['installed']}
                                                <span class="label label-success">Installed</span>
                                                {else}
                                                <span class="label label-default">Not Installed</span>
                                                {/if}
                                            </td>
                                        </tr>
                                        {/foreach}
                                        {/if}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <button type="button" class="btn btn-sm btn-success" style="font-weight: 600;" onclick="submitProfiles('install')">
                            Install on Selected Profiles
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" style="font-weight: 600;" onclick="submitProfiles('remove')">
                            Remove from Selected Profiles
                        </button>
                    </div>
                </form>
                {/if}
            </div>
        </div>
    </div>
</div>

<!-- 3. Manual Script Code Preview (Collapsible Accordion) -->
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-default" style="border-radius: 6px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
            <div class="panel-heading" style="cursor: pointer; background: #ffffff;" data-toggle="collapse" data-target="#manualScriptsCollapse">
                <strong style="color: #64748b; font-size: 13px;">View Manual Script Code (Optional) &darr;</strong>
            </div>
            <div id="manualScriptsCollapse" class="panel-collapse collapse">
                <div class="panel-body" style="padding: 16px;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="font-weight: 600; font-size: 12px;">PPPoE On-Up Script:</label>
                                <textarea class="form-control" rows="5" readonly style="font-family: monospace; font-size: 11px; background: #272822; color: #f8f8f2;">:local routerId "{$selectedRouterId}";
:local serverUrl "{$webhookUrl}";
:local sid [/ppp active get [find name=$user] session-id];
:local iface [/interface find where name=$interface];
:local linkUp [/interface get $iface last-link-up-time];
{literal}:if ($sid = "") do={ :set sid $user; }{/literal}
/tool fetch url=$serverUrl http-method=post http-data="action=session-start&router=$routerId&service=PPPoE&username=$user&session_id=$sid&mac=$caller-id&ip=$address&start_time=$linkUp" keep-result=no;</textarea>
                            </div>
                            <div class="form-group">
                                <label style="font-weight: 600; font-size: 12px;">PPPoE On-Down Script:</label>
                                <textarea class="form-control" rows="5" readonly style="font-family: monospace; font-size: 11px; background: #272822; color: #f8f8f2;">:local routerId "{$selectedRouterId}";
:local serverUrl "{$webhookUrl}";
:local secret [/ppp secret find where name=$user];
:local loggedOut [/ppp secret get $secret last-logged-out];
:local discReason [/ppp secret get $secret last-disconnect-reason];
/tool fetch url=$serverUrl http-method=post http-data="action=session-end&router=$routerId&service=PPPoE&username=$user&closed_time=$loggedOut&close_reason=$discReason" keep-result=no;</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="font-weight: 600; font-size: 12px;">Hotspot On-Login Script:</label>
                                <textarea class="form-control" rows="5" readonly style="font-family: monospace; font-size: 11px; background: #272822; color: #f8f8f2;">:local routerId "{$selectedRouterId}";
:local serverUrl "{$webhookUrl}";
/tool fetch url=$serverUrl http-method=post http-data="action=session-start&router=$routerId&service=Hotspot&username=$user&mac=$"mac-address"&ip=$address" keep-result=no;</textarea>
                            </div>
                            <div class="form-group">
                                <label style="font-weight: 600; font-size: 12px;">Hotspot On-Logout Script:</label>
                                <textarea class="form-control" rows="5" readonly style="font-family: monospace; font-size: 11px; background: #272822; color: #f8f8f2;">:local routerId "{$selectedRouterId}";
:local serverUrl "{$webhookUrl}";
/tool fetch url=$serverUrl http-method=post http-data="action=session-end&router=$routerId&service=Hotspot&username=$user&mac=$"mac-address"&ip=$address&uptime_secs=$"uptime-secs"&bytes_in=$"bytes-in"&bytes_out=$"bytes-out"&close_reason=$cause" keep-result=no;</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleCheckboxes(className, state) {
    var boxes = document.querySelectorAll('.' + className);
    boxes.forEach(function(b) {
        b.checked = state;
    });
}

function submitProfiles(action) {
    var checked = document.querySelectorAll('.chk_ppp_:checked, .chk_hs_:checked');
    if (checked.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Profiles Selected',
            text: 'Please select at least one PPPoE or Hotspot profile using the checkboxes.'
        });
        return;
    }

    var titleText = (action === 'install') 
        ? 'Install scripts on ' + checked.length + ' profile(s)?' 
        : 'Remove scripts from ' + checked.length + ' profile(s)?';

    Swal.fire({
        title: titleText,
        text: (action === 'install') 
            ? 'This will inject the on-up/on-down and on-login/on-logout tracking scripts via API.' 
            : 'This will clear the tracking scripts from the selected profiles.',
        icon: (action === 'install') ? 'question' : 'warning',
        showCancelButton: true,
        confirmButtonColor: (action === 'install') ? '#10b981' : '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: (action === 'install') ? 'Yes, Install' : 'Yes, Remove'
    }).then(function(result) {
        if (result.isConfirmed) {
            document.getElementById('profileActionInput').value = action;
            document.getElementById('profileScriptsForm').submit();
        }
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
</script>

{include file="sections/footer.tpl"}
