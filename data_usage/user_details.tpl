{include file="sections/header.tpl"}

<style>
table th {
    text-transform: none !important;
    font-weight: 600 !important;
}
</style>

<div class="row" style="margin-bottom: 20px;">
    <div class="col-md-12" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="javascript:history.back()" class="btn btn-sm btn-default" style="font-weight: 600; border-radius: 8px;">
                <i class="fa fa-arrow-left"></i> Back
            </a>
            <h3 class="data-usage-header-title" style="margin: 0; font-size: 22px; font-weight: 700; color: #1e293b; cursor: pointer;" title="Data Usage Engine">
                <i class="fa fa-user text-primary"></i> User Details &mdash; {$username}
            </h3>
        </div>
        <div class="btn-group">
            <a class="btn btn-primary btn-xs" href="{$_url}plugin/data_usage_admin">
                <i class="fa fa-dashboard"></i> Dashboard
            </a>
            <a class="btn btn-warning btn-xs" href="{$_url}plugin/data_usage_scripts">
                <i class="fa fa-cogs"></i> Settings
            </a>
        </div>
    </div>
</div>

<!-- Customer Information & All-Time Stats -->
<div class="row">
    <div class="col-md-4">
        {*
        <!-- Customer Information Section (Uncomment to re-enable in future) -->
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">
                Customer Information
            </div>
            <div class="panel-body">
                <table class="table table-condensed" style="margin-bottom: 0;">
                    <tr>
                        <th style="width: 100px;">Username:</th>
                        <td><strong>{$username}</strong></td>
                    </tr>
                    <tr>
                        <th>Full Name:</th>
                        <td>{$customer->fullname|default:'-'}</td>
                    </tr>
                    <tr>
                        <th>Phone:</th>
                        <td>{$customer->phonenumber|default:'-'}</td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td>{$customer->email|default:'-'}</td>
                    </tr>
                    <tr>
                        <th>Address:</th>
                        <td>{$customer->address|default:'-'}</td>
                    </tr>
                </table>
            </div>
        </div>
        *}

        <div class="small-box bg-purple">
            <div class="inner">
                <h4 class="text-bold" style="font-size: large;">{$allTimeBytesFmt}</h4>
            </div>
            <div class="icon">
                <i class="ion ion-ios-pulse-strong"></i>
            </div>
            <a href="#" class="small-box-footer">All-Time Tracked Data Usage</a>
        </div>
    </div>

    <!-- Daily Breakdown -->
    <div class="col-md-8">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">
                Daily Usage Summary (Last 30 Days)
            </div>
            <div class="table-responsive" style="max-height: 290px; overflow-y: auto;">
                <table class="table table-bordered table-striped" style="margin-bottom: 0;">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Service</th>
                            <th>Upload</th>
                            <th>Download</th>
                            <th>Total Usage</th>
                        </tr>
                    </thead>
                    <tbody>
                        {if count($dailySummary) == 0}
                        <tr>
                            <td colspan="5" class="text-center text-muted" style="padding: 20px;">No daily usage records available.</td>
                        </tr>
                        {else}
                        {foreach $dailySummary as $d}
                        <tr>
                            <td>{$d['usage_date']}</td>
                            <td>
                                {if $d['service'] == 'PPPoE'}
                                <span class="label label-primary">PPPoE</span>
                                {else}
                                <span class="label label-warning">Hotspot</span>
                                {/if}
                            </td>
                            <td>{$d['bytes_in_fmt']}</td>
                            <td>{$d['bytes_out_fmt']}</td>
                            <td><strong>{$d['total_bytes_fmt']}</strong></td>
                        </tr>
                        {/foreach}
                        {/if}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Session History -->
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <span style="font-weight: 700;">Session Logs (Recent 100 Sessions)</span>
                <div style="display: flex; align-items: center; gap: 6px;">
                    {if !empty($routers) && count($routers) > 0}
                    <button type="button" class="btn btn-xs btn-default" onclick="checkLiveStatus('{$username}', '{$userRouterId}', '{$userService}', this)" style="font-weight: 600;">
                        <i class="fa fa-refresh"></i> Refresh Live Status
                    </button>
                    {/if}
                    {if !empty($activeSessionId)}
                    <button type="button" class="btn btn-xs btn-danger" onclick="disconnectUser('{$activeSessionId}', '{$activeRouterId}', '{$username}')" style="font-weight: 600;">
                        <i class="fa fa-power-off"></i> Disconnect
                    </button>
                    {/if}
                </div>
            </div>
            <div class="panel-body" style="padding: 15px;">
                <div class="table-responsive">
                    <table id="datatable" class="table table-bordered table-striped table-hover" style="margin-bottom: 0; width: 100%;">
                        <thead>
                            <tr>
                                <th>Session ID</th>
                                <th>Service</th>
                                <th>Router</th>
                                <th>IP / MAC Address</th>
                                <th>Status</th>
                                <th>Start Time</th>
                                <th>Closed Time</th>
                                <th>Duration</th>
                                <th>Upload</th>
                                <th>Download</th>
                                <th>Total Usage</th>
                                <th>Close Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            {if count($sessions) == 0}
                            <tr>
                                <td colspan="12" class="text-center text-muted" style="padding: 25px;">
                                    No session history found for this user.
                                </td>
                            </tr>
                            {else}
                            {foreach $sessions as $s}
                            <tr>
                                <td><span style="font-family: monospace; font-size: 11px;">{$s['session_id']}</span></td>
                                <td>
                                    {if $s['service'] == 'PPPoE'}
                                    <span class="label label-primary">PPPoE</span>
                                    {else}
                                    <span class="label label-warning">Hotspot</span>
                                    {/if}
                                </td>
                                <td>{$s['router_name']}</td>
                                <td class="col-ip-mac">
                                    <span style="font-family: monospace; font-size: 11px;">{$s['ip_address']}</span>
                                    {if !empty($s['mac_address'])}
                                    <br><span class="text-muted" style="font-family: monospace; font-size: 11px;">{$s['mac_address']}</span>
                                    {/if}
                                </td>
                                <td class="col-status">
                                    {if $s['status'] == 'active'}
                                    <span class="label label-success">Online</span>
                                    {else}
                                    <span class="label label-default">Closed</span>
                                    {/if}
                                </td>
                                <td data-sort="{$s['start_time']}"><small>{$s['start_time']}</small></td>
                                <td data-sort="{$s['closed_time']|default:''}"><small>{$s['closed_time']|default:'-'}</small></td>
                                <td class="col-duration">{$s['duration_fmt']}</td>
                                <td class="col-upload" data-sort="{$s['bytes_in']}"><span class="text-info">{$s['bytes_in_fmt']}</span></td>
                                <td class="col-download" data-sort="{$s['bytes_out']}"><span class="text-success">{$s['bytes_out_fmt']}</span></td>
                                <td class="col-total" data-sort="{$s['total_bytes']}"><strong>{$s['total_bytes_fmt']}</strong></td>
                                <td class="col-reason">
                                    {if !empty($s['close_reason'])}
                                    <span class="label label-danger">{$s['close_reason']}</span>
                                    {else}
                                    <span class="text-muted">-</span>
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
    </div>
</div>

<!-- Hidden Disconnect Form -->
<form id="disconnectForm" method="POST" action="{$_url}plugin/data_usage_disconnect" style="display: none;">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    <input type="hidden" name="session_id" id="dc_session_id">
    <input type="hidden" name="router_id" id="dc_router_id">
    <input type="hidden" name="username" id="dc_username">
    <input type="hidden" name="redirect_to" value="user">
</form>

{include file="sections/footer.tpl"}

<script>
$(document).ready(function() {
    $('#datatable').DataTable({
        "pagingType": "full_numbers",
        "order": [[ 5, "desc" ]]
    });
});

function disconnectUser(sessionId, routerId, username) {
    Swal.fire({
        title: 'Disconnect User?',
        text: 'Are you sure you want to disconnect active session for "' + username + '"?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Disconnect'
    }).then(function(result) {
        if (result.isConfirmed) {
            document.getElementById('dc_session_id').value = sessionId;
            document.getElementById('dc_router_id').value = routerId;
            document.getElementById('dc_username').value = username;
            document.getElementById('disconnectForm').submit();
        }
    });
}

function checkLiveStatus(username, routerId, service, btn) {
    var $btn = $(btn);
    var origHtml = $btn.html();
    $btn.html('<i class="fa fa-spinner fa-spin"></i> Refreshing...').prop('disabled', true);

    $.ajax({
        url: '{$_url}plugin/data_usage_live_status',
        type: 'POST',
        data: {
            username: username,
            router_id: routerId,
            service: service
        },
        dataType: 'json',
        success: function(res) {
            $btn.html(origHtml).prop('disabled', false);
            if (res.status === 'success') {
                var $activeRow = $('tr:has(.label-success)');
                if ($activeRow.length && res.online) {
                    var ipMacHtml = '<span style="font-family: monospace; font-size: 11px;">' + (res.ip || '-') + '</span>';
                    if (res.mac) {
                        ipMacHtml += '<br><span class="text-muted" style="font-family: monospace; font-size: 11px;">' + res.mac + '</span>';
                    }
                    $activeRow.find('.col-ip-mac').html(ipMacHtml);
                    $activeRow.find('.col-duration').text(res.uptime || '-');
                    $activeRow.find('.col-upload').html('<span class="text-info">' + res.upload + '</span>');
                    $activeRow.find('.col-download').html('<span class="text-success">' + res.download + '</span>');
                    $activeRow.find('.col-total').html('<strong>' + res.total + '</strong>');

                    // Flash subtle green highlight on the row
                    $activeRow.css({ 'background-color': '#d1fae5', 'transition': 'background-color 0.3s ease' });
                    setTimeout(function() {
                        $activeRow.css({ 'background-color': '', 'transition': 'background-color 1s ease' });
                    }, 1000);
                } else if ($activeRow.length && !res.online) {
                    $activeRow.find('.col-status').html('<span class="label label-default">Closed</span>');
                    $activeRow.find('.col-reason').html('<span class="label label-danger">' + (res.last_disconnect_reason || 'Offline') + '</span>');
                    $activeRow.css({ 'background-color': '#fee2e2', 'transition': 'background-color 0.3s ease' });
                    setTimeout(function() {
                        $activeRow.css({ 'background-color': '', 'transition': 'background-color 1s ease' });
                    }, 1500);
                } else {
                    location.reload();
                }
            } else {
                Swal.fire({ icon: 'error', title: 'Status Check', text: res.message || 'Failed to query live status' });
            }
        },
        error: function() {
            $btn.html(origHtml).prop('disabled', false);
            Swal.fire({ icon: 'error', title: 'Connection Error', text: 'Connection error while querying router API.' });
        }
    });
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

    var titles = document.querySelectorAll('.data-usage-header-title, h3');
    titles.forEach(function(el) {
        if (el.innerText.toLowerCase().indexOf('user') !== -1 || el.innerText.toLowerCase().indexOf('data usage') !== -1) {
            el.style.cursor = 'pointer';
            el.title = "Developer Info";
            el.addEventListener('click', triggerEgg);
        }
    });
}
document.addEventListener('DOMContentLoaded', initDeveloperPopups);
</script>
