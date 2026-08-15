{include file="sections/header.tpl"}

<style>
table th {
    text-transform: none !important;
    font-weight: 600 !important;
}
.router-card {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    padding: 12px 16px 10px 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
    transition: all 0.2s ease;
    cursor: pointer;
    text-decoration: none !important;
    display: block;
    margin-bottom: 14px;
    color: inherit;
}
.router-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
    border-color: #cbd5e1;
    color: inherit;
}
.router-card-offline {
    background: #fff5f5 !important;
    border-color: #fecaca !important;
    opacity: 0.85;
    cursor: pointer !important;
}
.router-card-offline:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(239, 68, 68, 0.12) !important;
    border-color: #f87171 !important;
    color: inherit;
}
.stat-number-online {
    font-size: 20px;
    font-weight: 700;
    color: #0ca678;
    line-height: 1;
}
.stat-number-offline {
    font-size: 17px;
    font-weight: 700;
    color: #dc2626;
    line-height: 1;
}
.stat-label {
    font-size: 11px;
    font-weight: 500;
    color: #64748b;
    margin-top: 2px;
}
.stat-label-offline {
    font-size: 11px;
    font-weight: 500;
    color: #ef4444;
    margin-top: 2px;
}
.sync-timestamp {
    margin-top: 8px;
    font-size: 10px;
    color: #94a3b8;
    text-align: center;
    font-weight: 500;
}
</style>

<!-- Top Navigation & Action Toolbar -->
<div class="row" style="margin-bottom: 16px;">
    <div class="col-sm-12" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            {if $filterRouter > 0}
            <a href="javascript:history.back()" class="btn btn-sm btn-default" style="font-weight: 600; border-radius: 6px;">
                <i class="fa fa-arrow-left"></i> Back
            </a>
            {/if}
            <h3 class="data-usage-header-title" style="margin: 0; font-size: 20px; font-weight: 700; color: #1e293b; cursor: pointer;" title="Data Usage Engine">
                <i class="fa fa-dashboard text-primary"></i> Dashboard {if $filterRouter > 0}&mdash; {$currentRouter->name}{/if}
            </h3>
        </div>
        <div class="btn-group" style="display: flex; align-items: center; gap: 4px;">
            <a class="btn btn-primary btn-xs active" href="{$_url}plugin/data_usage_admin">
                <i class="fa fa-dashboard"></i> Dashboard
            </a>
            <a class="btn btn-warning btn-xs" href="{$_url}plugin/data_usage_scripts">
                <i class="fa fa-cogs"></i> Settings
            </a>
            <form id="syncAllForm" method="POST" action="{$_url}plugin/data_usage_sync" style="display: inline-block; margin: 0;">
                <input type="hidden" name="csrf_token" value="{$csrf_token}">
                <input type="hidden" name="router_id" value="0">
                <button type="button" class="btn btn-success btn-xs" style="border-radius: 3px;" onclick="confirmSyncAll()">
                    <i class="fa fa-refresh"></i> Sync Now
                </button>
            </form>
        </div>
    </div>
</div>

{if $filterRouter == 0}
<!-- =========================================================================
     VIEW 1: ROUTER SELECTION CARDS (DASHBOARD OVERVIEW)
     ========================================================================= -->
<div class="row" id="routerCardsContainer">
    {if count($routerCards) == 0}
    <div class="col-sm-12">
        <div class="panel panel-default" style="padding: 30px; text-align: center; border-radius: 10px;">
            <i class="fa fa-server fa-2x text-muted" style="margin-bottom: 10px;"></i>
            <h4>No active routers found.</h4>
            <p class="text-muted">Please configure and enable your routers under System &rarr; Routers.</p>
        </div>
    </div>
    {else}
    {foreach $routerCards as $rc}
    <div class="col-md-3 col-sm-6 col-xs-12">
        <a href="{$_url}plugin/data_usage_admin&router={$rc['id']}" id="router_card_{$rc['id']}" class="router-card {if !$rc['online']}router-card-offline{/if}" data-router-id="{$rc['id']}">
            <!-- Router Name (Centered) -->
            <h4 id="rc_name_{$rc['id']}" style="margin: 0 0 6px 0; font-size: 15px; font-weight: 700; color: {if $rc['online']}#1e293b{else}#991b1b{/if}; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                {$rc['name']}
            </h4>

            <!-- Online User Count Only -->
            <div id="rc_body_{$rc['id']}" class="text-center" style="padding: 2px 0;">
                <div class="stat-number-online" id="rc_count_{$rc['id']}">
                    {$rc['online_count']} <i class="fa fa-circle-o-notch fa-spin text-muted rc-spin" style="font-size: 11px; vertical-align: middle; opacity: 0.5;" id="rc_spin_{$rc['id']}"></i>
                </div>
                <div class="stat-label" id="rc_label_{$rc['id']}">Online</div>
            </div>

            <!-- Last Sync Timestamp (Centered) -->
            <div class="sync-timestamp" id="rc_sync_{$rc['id']}">
                Last Sync: {$rc['last_sync']}
            </div>
        </a>
    </div>
    {/foreach}
    {/if}
</div>

{else}
<!-- =========================================================================
     VIEW 2: SPECIFIC ROUTER USERS & SESSIONS (ONLINE + OFFLINE HISTORY)
     ========================================================================= -->

{if !$routerIsOnline}
<div class="alert alert-warning" style="border-radius: 8px; margin-bottom: 16px;">
    <i class="fa fa-exclamation-triangle"></i> <strong>Router is currently offline / unreachable.</strong> Displaying cached user registry and last recorded session history.
</div>
{/if}

<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <h4 style="margin: 0; font-size: 14px; font-weight: bold; color: #fff;">
                    <i class="fa fa-users"></i> Users &amp; Sessions &mdash; {$currentRouter->name}
                </h4>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="label {if $routerIsOnline}label-success{else}label-danger{/if}" style="font-size: 11px;">
                        {if $routerIsOnline}<i class="fa fa-check-circle"></i> Router Online{else}<i class="fa fa-times-circle"></i> Router Offline{/if}
                    </span>
                    <button class="btn btn-xs btn-default" onclick="location.reload();" style="font-weight: 600;">
                        <i class="fa fa-refresh"></i> Refresh All
                    </button>
                </div>
            </div>

            <div class="panel-body" style="padding: 15px;">
                <!-- Quick Filter Toolbar -->
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 15px; background: #f8fafc; padding: 10px 14px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                        <!-- Service Filter -->
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <small class="text-muted" style="font-weight: 600;">Service:</small>
                            <div class="btn-group btn-group-xs" id="serviceFilterGroup">
                                <button type="button" class="btn btn-primary active" data-service="">All ({$totalUsersCount})</button>
                                <button type="button" class="btn btn-default" data-service="PPPoE">PPPoE</button>
                                <button type="button" class="btn btn-default" data-service="Hotspot">Hotspot</button>
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <small class="text-muted" style="font-weight: 600;">Status:</small>
                            <div class="btn-group btn-group-xs" id="statusFilterGroup">
                                <button type="button" class="btn btn-default" data-status="">All ({$totalUsersCount})</button>
                                <button type="button" class="btn btn-primary active" data-status="Online">Online ({$onlineCount})</button>
                                <button type="button" class="btn btn-default" data-status="Offline">Offline ({$offlineCount})</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="datatable" class="table table-bordered table-striped table-hover" style="margin-bottom: 0; width: 100%;">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Service</th>
                                <th>IP / MAC Address</th>
                                <th>Status</th>
                                <th>Uptime</th>
                                <th>Last Seen</th>
                                <th>Live Upload</th>
                                <th>Live Download</th>
                                <th>Total Usage</th>
                                <th style="width: 140px; text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {if count($sessions) == 0}
                            <tr>
                                <td colspan="10" class="text-center text-muted" style="padding: 30px;">
                                    No users found on {$currentRouter->name}.
                                </td>
                            </tr>
                            {else}
                            {foreach $sessions as $s}
                            <tr id="session_row_{$s['username']|regex_replace:'/[^a-zA-Z0-9_-]/':'_'}" data-service="{$s['service']}" data-status="{$s['status']}">
                                <td>
                                    <a href="{$_url}plugin/data_usage_user&username={$s['username']|urlencode}&router_id={$s['router_id']}" style="font-weight: 600;">
                                        {$s['username']}
                                    </a>
                                    {if !empty($s['fullname'])}
                                    <br><small class="text-muted">{$s['fullname']}</small>
                                    {/if}
                                </td>
                                <td class="col-service" data-search="{$s['service']}">
                                    {if $s['service'] == 'PPPoE'}
                                    <span class="label label-primary">PPPoE</span>
                                    {else}
                                    <span class="label label-warning">Hotspot</span>
                                    {/if}
                                </td>
                                <td class="col-ip-mac">
                                    {if $s['status'] == 'Online'}
                                        <span style="font-family: monospace; font-size: 11px;">{$s['ip_address']}</span>
                                        {if !empty($s['mac_address']) && $s['mac_address'] != '-'}
                                        <br><span class="text-muted" style="font-family: monospace; font-size: 11px;">{$s['mac_address']}</span>
                                        {/if}
                                    {else}
                                        {if !empty($s['mac_address']) && $s['mac_address'] != '-'}
                                        <span class="text-muted" style="font-family: monospace; font-size: 11px;">{$s['mac_address']}</span>
                                        {else}
                                        <span class="text-muted">-</span>
                                        {/if}
                                    {/if}
                                </td>
                                <td class="col-status" data-search="{$s['status']}" data-order="{if $s['status'] == 'Online'}1{else}2{/if}">
                                    {if $s['status'] == 'Online'}
                                    <span class="label label-success">Online</span>
                                    {else}
                                    <span class="label label-default">Offline</span>
                                    {/if}
                                </td>
                                <td class="col-uptime" data-sort="{$s['uptime_seconds']}">
                                    {if $s['status'] == 'Online'}
                                    <span class="text-primary" style="font-weight: 600;">{$s['uptime_fmt']}</span>
                                    {else}
                                    <span class="text-muted">-</span>
                                    {/if}
                                </td>
                                <td class="col-last-seen">
                                    {if $s['status'] == 'Online'}
                                    <span class="label label-success">Now</span>
                                    {else}
                                    <small class="text-muted">{$s['last_seen']}</small>
                                    {/if}
                                </td>
                                <td class="col-upload" data-sort="{$s['bytes_in']}"><span class="text-info">{$s['bytes_in_fmt']}</span></td>
                                <td class="col-download" data-sort="{$s['bytes_out']}"><span class="text-success">{$s['bytes_out_fmt']}</span></td>
                                <td class="col-total" data-sort="{$s['total_bytes']}"><strong>{$s['total_bytes_fmt']}</strong></td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <button type="button" class="btn btn-xs btn-primary" onclick="refreshRow('{$s['username']}', '{$s['router_id']}', '{$s['service']}', this)">
                                        Refresh
                                    </button>
                                    {if $s['status'] == 'Online'}
                                    <button type="button" class="btn btn-xs btn-danger" onclick="disconnectUser('{$s['session_id']}', '{$s['router_id']}', '{$s['username']}')">
                                        Disconnect
                                    </button>
                                    {/if}
                                    <a href="{$_url}plugin/data_usage_user&username={$s['username']|urlencode}&router_id={$s['router_id']}" class="btn btn-xs btn-info">
                                        History
                                    </a>
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
{/if}

<!-- Hidden Disconnect Form -->
<form id="disconnectForm" method="POST" action="{$_url}plugin/data_usage_disconnect" style="display: none;">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    <input type="hidden" name="session_id" id="dc_session_id">
    <input type="hidden" name="router_id" id="dc_router_id">
    <input type="hidden" name="username" id="dc_username">
    <input type="hidden" name="redirect_to" value="router">
</form>

<script>
function confirmSyncAll() {
    Swal.fire({
        title: 'Sync All Routers?',
        text: 'Query and sync user registries and active sessions across all enabled routers via API?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Sync Now'
    }).then(function(result) {
        if (result.isConfirmed) {
            document.getElementById('syncAllForm').submit();
        }
    });
}

function disconnectUser(sessionId, routerId, username) {
    Swal.fire({
        title: 'Disconnect User?',
        text: 'Are you sure you want to disconnect user "' + username + '"?',
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

function refreshRow(username, routerId, service, btn) {
    var $btn = $(btn);
    var $tr = $btn.closest('tr');
    var origHtml = $btn.html();
    $btn.html('<i class="fa fa-spinner fa-spin"></i>').prop('disabled', true);

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
            if (res.status === 'success' && res.online) {
                var ipMacHtml = '<span style="font-family: monospace; font-size: 11px;">' + (res.ip || '-') + '</span>';
                if (res.mac) {
                    ipMacHtml += '<br><span class="text-muted" style="font-family: monospace; font-size: 11px;">' + res.mac + '</span>';
                }
                $tr.find('.col-ip-mac').html(ipMacHtml);
                $tr.find('.col-status').html('<span class="label label-success">Online</span>').attr('data-search', 'Online').attr('data-order', '1');
                $tr.find('.col-uptime').html('<span class="text-primary" style="font-weight: 600;">' + (res.uptime || '-') + '</span>');
                $tr.find('.col-last-seen').html('<span class="label label-success">Now</span>');
                $tr.find('.col-upload').html('<span class="text-info">' + res.upload + '</span>');
                $tr.find('.col-download').html('<span class="text-success">' + res.download + '</span>');
                $tr.find('.col-total').html('<strong>' + res.total + '</strong>');

                // Flash subtle green highlight
                $tr.css({ 'background-color': '#d1fae5', 'transition': 'background-color 0.3s ease' });
                setTimeout(function() {
                    $tr.css({ 'background-color': '', 'transition': 'background-color 1s ease' });
                }, 1000);
            } else if (res.status === 'success' && !res.online) {
                var ipMacHtml = (res.mac && res.mac !== '-') ? '<span class="text-muted" style="font-family: monospace; font-size: 11px;">' + res.mac + '</span>' : '<span class="text-muted">-</span>';
                $tr.find('.col-ip-mac').html(ipMacHtml);
                $tr.find('.col-status').html('<span class="label label-default">Offline</span>').attr('data-search', 'Offline').attr('data-order', '2');
                $tr.find('.col-uptime').html('<span class="text-muted">-</span>').attr('data-sort', '0');
                $tr.find('.col-last-seen').html('<small class="text-muted">' + (res.last_logged_out || 'Offline') + '</small>');
                $tr.css({ 'background-color': '#fee2e2', 'transition': 'background-color 0.3s ease' });
                setTimeout(function() {
                    $tr.css({ 'background-color': '', 'transition': 'background-color 1s ease' });
                }, 1500);
            } else {
                Swal.fire({ icon: 'error', title: 'Status Check', text: res.message || 'Failed to refresh row' });
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
    if (!sessionStorage.getItem('dataUsageDevModalShown')) {
        sessionStorage.setItem('dataUsageDevModalShown', 'true');
        Swal.fire({
            title: 'Advance Data Usage',
            html: '<div style="text-align:left; font-size:13px; color:#475569; margin-bottom:10px; background:#f8fafc; padding:12px 14px; border-radius:8px; border:1px solid #e2e8f0;">' +
                  '<p style="margin-bottom:6px; color:#0f172a; font-weight:700;"><i class="fa fa-check-circle text-success"></i> Key Features:</p>' +
                  '<ul style="padding-left:18px; margin-bottom:12px; line-height:1.5;">' +
                  '<li><b>Direct RouterOS API:</b> Real-time active user tracking without polling delay</li>' +
                  '<li><b>Hardware Accounting:</b> Strict on-up/on-down sync with interface timestamps</li>' +
                  '<li><b>Multi-Router Live Monitor:</b> Instant reachability & connected counts</li>' +
                  '</ul>' +
                  '<div style="text-align:center; padding-top:6px; border-top:1px dashed #cbd5e1;">' +
                  '<p style="font-size:11px; color:#64748b; margin-bottom:8px;">Developed by <b>Mahmud</b></p>' +
                  '<a href="https://wa.me/+8801540221898?text=I%20want%20to%20help%20you%20buy%20a%20meal" target="_blank" class="btn btn-sm btn-success" style="border-radius:20px; padding:6px 18px; font-weight:600;">' +
                  '<i class="fa fa-whatsapp"></i> Donate / Buy a Meal ☕' +
                  '</a>' +
                  '</div>' +
                  '</div>',
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true,
            allowOutsideClick: true,
            backdrop: 'rgba(0,0,0,0.6)',
            didOpen: function(popup) {
                popup.addEventListener('mouseenter', Swal.stopTimer);
                popup.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
    }

    var triggerEgg = function() {
        Swal.fire({
            title: 'Advance Data Usage',
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
        if (el.innerText.toLowerCase().indexOf('dashboard') !== -1 || el.innerText.toLowerCase().indexOf('data usage') !== -1) {
            el.style.cursor = 'pointer';
            el.title = "Developer Info";
            el.addEventListener('click', triggerEgg);
        }
    });
}

document.addEventListener('DOMContentLoaded', initDeveloperPopups);
</script>

{include file="sections/footer.tpl"}

<script>
$(document).ready(function() {
    if ($('#routerCardsContainer').length) {
        // Query each router card in parallel independently so fast local routers update immediately
        $('.router-card[data-router-id]').each(function() {
            var rId = $(this).data('router-id');
            var $card = $('#router_card_' + rId);
            var $name = $('#rc_name_' + rId);
            var $count = $('#rc_count_' + rId);
            var $label = $('#rc_label_' + rId);
            var $sync = $('#rc_sync_' + rId);
            var $spin = $('#rc_spin_' + rId);

            $.ajax({
                url: '{$_url}plugin/data_usage_async_routers_status&router_id=' + rId,
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    $spin.remove();
                    if (res.status === 'success' && res.routers && res.routers[rId]) {
                        var data = res.routers[rId];
                        if (data.online) {
                            $card.removeClass('router-card-offline');
                            $name.css('color', '#1e293b');
                            $count.removeClass('stat-number-offline').addClass('stat-number-online').text(data.online_count);
                            $label.removeClass('stat-label-offline').addClass('stat-label').text('Online');
                            $sync.css('color', '#94a3b8').text('Last Sync: ' + data.last_sync);
                        } else {
                            $card.addClass('router-card-offline');
                            $name.css('color', '#991b1b');
                            $count.removeClass('stat-number-online').addClass('stat-number-offline').text('Offline');
                            $label.removeClass('stat-label').addClass('stat-label-offline').text('Unreachable');
                            $sync.css('color', '#f87171').text('Last Sync: ' + data.last_sync);
                        }
                    }
                },
                error: function() {
                    $spin.remove();
                }
            });
        });
    }

    if ($('#datatable').length) {
        var dt = $('#datatable').DataTable({
            "pagingType": "full_numbers",
            "order": [[ 0, "asc" ]] // Sort by Username ascending
        });

        // Apply default filter to Online on load
        dt.column(3).search('Online').draw();

        // Quick Filter by Service (Column index 1)
        $('#serviceFilterGroup button').on('click', function() {
            $('#serviceFilterGroup button').removeClass('active btn-primary').addClass('btn-default');
            $(this).addClass('active btn-primary').removeClass('btn-default');
            var svc = $(this).data('service');
            dt.column(1).search(svc || '').draw();
        });

        // Quick Filter by Status (Column index 3)
        $('#statusFilterGroup button').on('click', function() {
            $('#statusFilterGroup button').removeClass('active btn-primary').addClass('btn-default');
            $(this).addClass('active btn-primary').removeClass('btn-default');
            var st = $(this).data('status');
            dt.column(3).search(st || '').draw();
        });
    }
});
</script>
