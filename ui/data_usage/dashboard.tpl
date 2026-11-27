{include file="sections/header.tpl"}

<style>
table th {
    text-transform: none !important;
    font-weight: 600 !important;
    white-space: nowrap !important;
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
.router-card.router-card-loading {
    pointer-events: none !important;
    cursor: wait !important;
    opacity: 0.75;
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
/* Dark Mode Support for Router Cards & Toolbar */
body.dark-mode .router-card,
.dark-mode .router-card {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #f1f5f9 !important;
}
body.dark-mode .router-card:hover,
.dark-mode .router-card:hover {
    border-color: #64748b !important;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4) !important;
}
body.dark-mode .router-card .rc-title,
.dark-mode .router-card .rc-title {
    color: #f1f5f9 !important;
}
body.dark-mode .router-card .stat-label,
.dark-mode .router-card .stat-label {
    color: #94a3b8 !important;
}
body.dark-mode .router-card .sync-timestamp,
.dark-mode .router-card .sync-timestamp {
    color: #64748b !important;
}
body.dark-mode .router-card.router-card-offline,
.dark-mode .router-card.router-card-offline {
    background: rgba(239, 68, 68, 0.15) !important;
    border-color: rgba(239, 68, 68, 0.3) !important;
}
body.dark-mode .router-card.router-card-offline .rc-title,
.dark-mode .router-card.router-card-offline .rc-title {
    color: #f87171 !important;
}
.filter-toolbar {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}
body.dark-mode .filter-toolbar,
.dark-mode .filter-toolbar {
    background: #1e293b !important;
    border-color: #334155 !important;
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

/* 8-Dot Circular Spinner */
.dot-spinner {
    position: relative;
    display: inline-block;
    width: 24px;
    height: 24px;
    margin: 2px auto;
}
.dot-spinner-item {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}
.dot-spinner-item::before {
    content: '';
    display: block;
    margin: 0 auto;
    width: 5px;
    height: 5px;
    background-color: #0ca678;
    border-radius: 100%;
    animation: dotSpinnerFade 1.2s infinite ease-in-out both;
}
.dot-spinner-item:nth-child(1) { transform: rotate(0deg); }
.dot-spinner-item:nth-child(2) { transform: rotate(45deg); }
.dot-spinner-item:nth-child(3) { transform: rotate(90deg); }
.dot-spinner-item:nth-child(4) { transform: rotate(135deg); }
.dot-spinner-item:nth-child(5) { transform: rotate(180deg); }
.dot-spinner-item:nth-child(6) { transform: rotate(225deg); }
.dot-spinner-item:nth-child(7) { transform: rotate(270deg); }
.dot-spinner-item:nth-child(8) { transform: rotate(315deg); }

.dot-spinner-item:nth-child(1)::before { animation-delay: -1.05s; }
.dot-spinner-item:nth-child(2)::before { animation-delay: -0.9s; }
.dot-spinner-item:nth-child(3)::before { animation-delay: -0.75s; }
.dot-spinner-item:nth-child(4)::before { animation-delay: -0.6s; }
.dot-spinner-item:nth-child(5)::before { animation-delay: -0.45s; }
.dot-spinner-item:nth-child(6)::before { animation-delay: -0.3s; }
.dot-spinner-item:nth-child(7)::before { animation-delay: -0.15s; }
.dot-spinner-item:nth-child(8)::before { animation-delay: 0s; }

@keyframes dotSpinnerFade {
    0%, 39%, 100% {
        opacity: 0.2;
        transform: scale(0.6);
    }
    40% {
        opacity: 1;
        transform: scale(1.15);
    }
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
                <i class="fa fa-dashboard text-primary"></i> Dashboard {if $filterRouter > 0}- {$currentRouter->name}{/if}
            </h3>
        </div>
        <div class="btn-group" style="display: flex; align-items: center; gap: 4px;">
            <a class="btn btn-primary btn-xs active" href="{$_url}plugin/data_usage_admin">
                <i class="fa fa-dashboard"></i> Dashboard
            </a>
            <a class="btn btn-warning btn-xs" href="{$_url}plugin/data_usage_scripts">
                <i class="fa fa-cogs"></i> Settings
            </a>
            <a class="btn btn-info btn-xs" href="{$_url}plugin/data_usage_logs">
                <i class="fa fa-list-alt"></i> Logs
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
        <a href="{$_url}plugin/data_usage_admin&router={$rc['id']}" id="router_card_{$rc['id']}" class="router-card router-card-loading {if !$rc['online']}router-card-offline{/if}" data-router-id="{$rc['id']}">
            <!-- Router Name (Centered) -->
            <h4 id="rc_name_{$rc['id']}" class="rc-title" style="margin: 0 0 6px 0; font-size: 15px; font-weight: 700; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: {if $rc['online']}inherit{else}#dc2626{/if};">
                {$rc['name']}
            </h4>

            <!-- Online User Count / Dot Spinner -->
            <div id="rc_body_{$rc['id']}" class="text-center" style="padding: 2px 0; min-height: 44px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                <div class="stat-number-online" id="rc_count_{$rc['id']}" style="min-height: 24px; display: flex; align-items: center; justify-content: center;">
                    <div class="dot-spinner" id="rc_spin_{$rc['id']}">
                        <div class="dot-spinner-item"></div>
                        <div class="dot-spinner-item"></div>
                        <div class="dot-spinner-item"></div>
                        <div class="dot-spinner-item"></div>
                        <div class="dot-spinner-item"></div>
                        <div class="dot-spinner-item"></div>
                        <div class="dot-spinner-item"></div>
                        <div class="dot-spinner-item"></div>
                    </div>
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
    <strong>Router is offline.</strong> Showing last saved user history.
</div>
{/if}

<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <h4 style="margin: 0; font-size: 14px; font-weight: bold;">
                    Users and Sessions - {$currentRouter->name}
                </h4>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="label {if $routerIsOnline}label-success{else}label-danger{/if}" style="font-size: 11px;">
                        {if $routerIsOnline}Router Online{else}Router Offline{/if}
                    </span>
                    <button class="btn btn-xs btn-default" onclick="location.reload();" style="font-weight: 600;">
                        <i class="fa fa-refresh"></i> Refresh All
                    </button>
                </div>
            </div>

            <div class="panel-body" style="padding: 15px;">
                <!-- Quick Filter Toolbar -->
                <div class="filter-toolbar" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 15px; padding: 10px 14px; border-radius: 8px;">
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
                                <th>Upload</th>
                                <th>Download</th>
                                <th>Total Usage</th>
                                <th style="width: 140px; text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
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
                    $card.removeClass('router-card-loading');
                    $spin.remove();
                    if (res.status === 'success' && res.routers && res.routers[rId]) {
                        var data = res.routers[rId];
                        if (data.online) {
                            $card.removeClass('router-card-offline');
                            $name.css('color', '');
                            $count.removeClass('stat-number-offline').addClass('stat-number-online').text(data.online_count);
                            $label.removeClass('stat-label-offline').addClass('stat-label').text('Online');
                            $sync.css('color', '').text('Last Sync: ' + data.last_sync);
                        } else {
                            $card.addClass('router-card-offline');
                            $name.css('color', '#ef4444');
                            $count.removeClass('stat-number-online').addClass('stat-number-offline').text('Offline');
                            $label.removeClass('stat-label').addClass('stat-label-offline').text('Unreachable');
                            $sync.css('color', '#ef4444').text('Last Sync: ' + data.last_sync);
                        }
                    }
                },
                error: function() {
                    $card.removeClass('router-card-loading');
                    $spin.remove();
                }
            });
        });
    }

    if ($('#datatable').length) {
        var dt = $('#datatable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": '{$_url}plugin/data_usage_admin&router={$currentRouter->id}&ajax=1',
                "type": "GET"
            },
            "searchCols": [
                null,
                null,
                null,
                { "search": "Online" }
            ],
            "columnDefs": [
                { "orderable": false, "targets": [2, 5, 9] }
            ],
            "pagingType": "full_numbers",
            "order": [[ 0, "asc" ]]
        });

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
