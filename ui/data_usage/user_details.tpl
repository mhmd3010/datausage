{include file="sections/header.tpl"}

<style>
table th {
    text-transform: none !important;
    font-weight: 600 !important;
    white-space: nowrap !important;
}
</style>

<div class="row" style="margin-bottom: 20px;">
    <div class="col-md-12" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="javascript:history.back()" class="btn btn-sm btn-default" style="font-weight: 600; border-radius: 6px;">
                <i class="fa fa-arrow-left"></i> Back
            </a>
            <h3 class="data-usage-header-title" style="margin: 0; font-size: 22px; font-weight: 700; color: #1e293b; cursor: pointer;" title="Data Usage Engine">
                <i class="fa fa-user text-primary"></i> User Details - {$username} {if $targetRouter}<span style="font-size: 16px; font-weight: normal; color: #64748b;">({$targetRouter->name})</span>{/if}
            </h3>
        </div>
        <div class="btn-group">
            <a class="btn btn-primary btn-xs" href="{$_url}plugin/data_usage_admin">
                <i class="fa fa-dashboard"></i> Dashboard
            </a>
            <a class="btn btn-warning btn-xs" href="{$_url}plugin/data_usage_scripts">
                <i class="fa fa-cogs"></i> Settings
            </a>
            <a class="btn btn-info btn-xs" href="{$_url}plugin/data_usage_logs">
                <i class="fa fa-list-alt"></i> Logs
            </a>
        </div>
    </div>
</div>

<!-- Stat Cards: All-Time & Monthly Usage -->
<div class="row" style="margin-bottom: 20px;">
    <div class="col-sm-6 col-xs-12">
        <div class="small-box bg-purple" style="border-radius: 8px; margin-bottom: 10px;">
            <div class="inner" style="padding: 12px 16px 10px 16px;">
                <h4 class="text-bold" style="font-size: large; margin: 0 0 2px 0;">{$allTimeBytesFmt}</h4>
                <p style="margin: 0; font-size: 12px; opacity: 0.9;">All-Time Usage</p>
            </div>
            <a href="javascript:void(0)" class="small-box-footer" style="padding: 4px 16px; text-align: left; font-size: 11px; cursor: default;">
                Total Tracked Data
            </a>
        </div>
    </div>

    <div class="col-sm-6 col-xs-12">
        <div class="small-box bg-aqua" style="border-radius: 8px; margin-bottom: 10px; cursor: pointer;" data-toggle="modal" data-target="#dailyUsageModal">
            <div class="inner" style="padding: 12px 16px 10px 16px;">
                <h4 class="text-bold" style="font-size: large; margin: 0 0 2px 0;">{$monthlyBytesFmt}</h4>
                <p style="margin: 0; font-size: 12px; opacity: 0.9;">{$currentMonthName}</p>
            </div>
            <a href="javascript:void(0)" class="small-box-footer" style="padding: 4px 16px; text-align: left; font-size: 11px;">
                Daily Usage
            </a>
        </div>
    </div>
</div>

<!-- Modal: Daily Usage Table -->
<div class="modal fade" id="dailyUsageModal" tabindex="-1" role="dialog" aria-labelledby="dailyUsageModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius: 8px;">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="dailyUsageModalLabel" style="font-weight: 700;">
                    Daily Usage (Last 30 Days) - {$username}
                </h4>
            </div>
            <div class="modal-body" style="padding: 0;">
                <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
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
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-sm" data-dismiss="modal" style="font-weight: 600;">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Session History -->
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <span style="font-weight: 700;">Recent Sessions (Last 100)</span>
                <div style="display: flex; align-items: center; gap: 6px;">
                    {if !empty($routers) && count($routers) > 0}
                    <button type="button" class="btn btn-xs btn-default" onclick="checkLiveStatus('{$username}', '{$userRouterId}', 'all', this)" style="font-weight: 600;">
                        Check Live Status
                    </button>
                    <button type="button" class="btn btn-xs btn-info" onclick="openTrafficModal('{$username}', '{$userRouterId}', '{$userService}')" style="font-weight: 600; background-color: #0284c7; border-color: #0284c7;">
                        Monitor Traffic
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

<!-- Traffic Monitor Modal -->
<div class="modal fade" id="trafficModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document" style="max-width: 680px; margin: 30px auto;">
        <div class="modal-content" style="border-radius: 8px; overflow: hidden; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15); border: 1px solid #e2e8f0;">
            <div class="modal-header" style="background: #ffffff; border-bottom: 1px solid #e2e8f0; padding: 12px 18px;">
                <span id="tm_status_badge" class="label label-success pull-right" style="font-size: 11px; padding: 4px 8px; border-radius: 3px; margin-top: 2px;">
                    Live
                </span>
                <h4 class="modal-title" style="margin: 0; font-size: 15px; font-weight: 700; color: #1e293b; text-align: left;">
                    Live Traffic - <span id="tm_username">{$username}</span>
                </h4>
            </div>
            <div class="modal-body" style="padding: 16px; background: #fafafa;">
                <!-- Chart.js Graph Canvas -->
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                    <div style="position: relative; height: 190px; width: 100%;">
                        <canvas id="tm_chart_canvas"></canvas>
                    </div>
                </div>

                <!-- Bottom Stats Cards (Matching User Reference) -->
                <div class="row" style="margin-top: 14px; margin-bottom: 0;">
                    <div class="col-sm-6 col-xs-12" style="margin-bottom: 10px;">
                        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-left: 4px solid #1d72b8; border-radius: 4px; padding: 12px 15px; box-shadow: 0 1px 2px rgba(0,0,0,0.04);">
                            <div style="font-size: 11px; font-weight: 700; color: #1d72b8; letter-spacing: 0.5px; text-transform: uppercase;">
                                DOWNLOAD (TX)
                            </div>
                            <div style="margin-top: 4px; display: flex; align-items: baseline; gap: 6px;">
                                <span id="tm_download_rate" style="font-size: 20px; font-weight: 700; color: #1d72b8; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">0 bps</span>
                                <span id="tm_download_state" class="text-muted" style="font-size: 12px;">(Live)</span>
                            </div>
                            <div id="tm_download_stats" class="text-muted" style="font-size: 11px; margin-top: 4px; line-height: 1.3;">
                                Min: 0 bps | Avg: 0 bps | Max: 0 bps
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xs-12" style="margin-bottom: 10px;">
                        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-left: 4px solid #7e22ce; border-radius: 4px; padding: 12px 15px; box-shadow: 0 1px 2px rgba(0,0,0,0.04);">
                            <div style="font-size: 11px; font-weight: 700; color: #7e22ce; letter-spacing: 0.5px; text-transform: uppercase;">
                                UPLOAD (RX)
                            </div>
                            <div style="margin-top: 4px; display: flex; align-items: baseline; gap: 6px;">
                                <span id="tm_upload_rate" style="font-size: 20px; font-weight: 700; color: #7e22ce; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">0 bps</span>
                                <span id="tm_upload_state" class="text-muted" style="font-size: 12px;">(Live)</span>
                            </div>
                            <div id="tm_upload_stats" class="text-muted" style="font-size: 11px; margin-top: 4px; line-height: 1.3;">
                                Min: 0 bps | Avg: 0 bps | Max: 0 bps
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="padding: 10px 18px; background: #ffffff; border-top: 1px solid #e2e8f0;">
                <button type="button" id="tm_resume_btn" class="btn btn-sm btn-primary" onclick="restartTrafficPolling()" style="font-weight: 600;">
                    Restart Stream
                </button>
                <button type="button" class="btn btn-sm btn-default" data-dismiss="modal" style="font-weight: 600;">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}

<script>
$(document).ready(function() {
    $('#datatable').DataTable({
        "pagingType": "full_numbers",
        "order": [[ 5, "desc" ]]
    });

    $('#trafficModal').on('hidden.bs.modal', function () {
        closeTrafficModal();
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

// ── Live Traffic Monitoring Engine (Chart.js + Configurable Chained Polling) ──
var tmPollMs = parseInt('{$trafficPollMs|default:100}') || 100;

var tmState = {
    isOpen: false,
    isFetching: false,
    username: '',
    routerId: 0,
    service: 'PPPoE',
    endTime: 0,
    pollTimeout: null,
    chart: null,
    downloadHistory: [],
    uploadHistory: [],
    timeLabels: [],
    maxPoints: 200
};

function openTrafficModal(username, routerId, service) {
    tmState.username = username;
    tmState.routerId = routerId;
    tmState.service = service || 'PPPoE';
    tmState.isOpen = true;
    tmState.isFetching = false;
    tmState.downloadHistory = [];
    tmState.uploadHistory = [];
    tmState.timeLabels = [];

    $('#tm_username').text(username);
    $('#tm_download_rate').text('0 bps');
    $('#tm_upload_rate').text('0 bps');
    $('#tm_download_state').text('(Live)');
    $('#tm_upload_state').text('(Live)');
    $('#tm_download_stats').text('Min: 0 bps | Avg: 0 bps | Max: 0 bps');
    $('#tm_upload_stats').text('Min: 0 bps | Avg: 0 bps | Max: 0 bps');
    $('#tm_status_badge').removeClass('label-default').addClass('label-success').text('Live');

    $('#trafficModal').modal('show');

    setTimeout(function() {
        initChartJs();
        restartTrafficPolling();
    }, 250);
}

function closeTrafficModal() {
    tmState.isOpen = false;
    clearTimeout(tmState.pollTimeout);
    setTrafficStatusUI(false);
}

function restartTrafficPolling() {
    clearTimeout(tmState.pollTimeout);
    tmState.isOpen = true;
    tmState.isFetching = false;
    tmState.endTime = Date.now() + 15000; // 15-second background limit
    setTrafficStatusUI(true);

    // Start chained polling
    pollTrafficChained();
}

function setTrafficStatusUI(isActive) {
    if (isActive) {
        $('#tm_status_badge').removeClass('label-default').addClass('label-success').text('Live');
        $('#tm_download_state').text('(Live)');
        $('#tm_upload_state').text('(Live)');
        $('#tm_resume_btn').removeClass('btn-success').addClass('btn-primary').text('Restart Monitor');
    } else {
        $('#tm_status_badge').removeClass('label-success').addClass('label-default').text('Stopped');
        $('#tm_download_state').text('(Stopped)');
        $('#tm_upload_state').text('(Stopped)');
        $('#tm_resume_btn').removeClass('btn-primary').addClass('btn-success').text('Restart Monitor');
    }
}

function pollTrafficChained() {
    if (!tmState.isOpen) return;
    if (Date.now() >= tmState.endTime) {
        setTrafficStatusUI(false);
        return;
    }
    if (tmState.isFetching) return;

    tmState.isFetching = true;
    var reqStart = Date.now();

    $.ajax({
        url: '{$_url}plugin/data_usage_live_traffic',
        type: 'GET',
        data: {
            router_id: tmState.routerId,
            username: tmState.username,
            service: tmState.service
        },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success' && tmState.isOpen) {
                if (res.poll_ms) {
                    tmPollMs = parseInt(res.poll_ms) || tmPollMs;
                }
                var downBps = parseFloat(res.download_bps) || 0;
                var upBps = parseFloat(res.upload_bps) || 0;

                $('#tm_download_rate').text(res.download_fmt || '0 bps');
                $('#tm_upload_rate').text(res.upload_fmt || '0 bps');

                var now = new Date();
                var timeStr = now.toLocaleTimeString();

                tmState.downloadHistory.push(downBps);
                tmState.uploadHistory.push(upBps);
                tmState.timeLabels.push(timeStr);

                if (tmState.downloadHistory.length > tmState.maxPoints) {
                    tmState.downloadHistory.shift();
                    tmState.uploadHistory.shift();
                    tmState.timeLabels.shift();
                }

                updateStatsSummary();
                updateChartData();
            }
        },
        complete: function() {
            tmState.isFetching = false;
            if (tmState.isOpen && Date.now() < tmState.endTime) {
                // Fixed gap between requests as configured in settings (e.g. 500ms)
                tmState.pollTimeout = setTimeout(pollTrafficChained, tmPollMs);
            } else if (tmState.isOpen && Date.now() >= tmState.endTime) {
                setTrafficStatusUI(false);
            }
        }
    });
}

function updateStatsSummary() {
    if (!tmState.downloadHistory.length) return;

    var dMin = Infinity, dMax = 0, dSum = 0;
    var uMin = Infinity, uMax = 0, uSum = 0;

    for (var i = 0; i < tmState.downloadHistory.length; i++) {
        var d = tmState.downloadHistory[i];
        if (d < dMin) dMin = d;
        if (d > dMax) dMax = d;
        dSum += d;

        var u = tmState.uploadHistory[i];
        if (u < uMin) uMin = u;
        if (u > uMax) uMax = u;
        uSum += u;
    }

    var count = tmState.downloadHistory.length;
    var dAvg = dSum / count;
    var uAvg = uSum / count;
    if (dMin === Infinity) dMin = 0;
    if (uMin === Infinity) uMin = 0;

    $('#tm_download_stats').text('Min: ' + formatBpsJs(dMin) + ' | Avg: ' + formatBpsJs(dAvg) + ' | Max: ' + formatBpsJs(dMax));
    $('#tm_upload_stats').text('Min: ' + formatBpsJs(uMin) + ' | Avg: ' + formatBpsJs(uAvg) + ' | Max: ' + formatBpsJs(uMax));
}

function initChartJs() {
    var canvas = document.getElementById('tm_chart_canvas');
    if (!canvas) return;
    var ctx = canvas.getContext('2d');

    if (tmState.chart) {
        tmState.chart.destroy();
        tmState.chart = null;
    }

    var gradDown = ctx.createLinearGradient(0, 0, 0, 190);
    gradDown.addColorStop(0, 'rgba(29, 114, 184, 0.18)');
    gradDown.addColorStop(1, 'rgba(29, 114, 184, 0.01)');

    var gradUp = ctx.createLinearGradient(0, 0, 0, 190);
    gradUp.addColorStop(0, 'rgba(126, 34, 206, 0.18)');
    gradUp.addColorStop(1, 'rgba(126, 34, 206, 0.01)');

    tmState.chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: tmState.timeLabels,
            datasets: [
                {
                    label: 'Download (TX)',
                    data: tmState.downloadHistory,
                    borderColor: '#1d72b8',
                    backgroundColor: gradDown,
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2.5,
                    pointRadius: 2.5,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#1d72b8'
                },
                {
                    label: 'Upload (RX)',
                    data: tmState.uploadHistory,
                    borderColor: '#7e22ce',
                    backgroundColor: gradUp,
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2.5,
                    pointRadius: 2.5,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#7e22ce'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    align: 'center',
                    labels: {
                        usePointStyle: false,
                        boxWidth: 26,
                        boxHeight: 12,
                        font: {
                            size: 11,
                            weight: '600',
                            family: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
                        },
                        color: '#475569',
                        padding: 12
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(30, 41, 59, 0.95)',
                    titleFont: { size: 11, weight: 'bold' },
                    bodyFont: { size: 11 },
                    padding: 8,
                    cornerRadius: 4,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + formatBpsJs(context.raw);
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        maxTicksLimit: 7,
                        font: { size: 10 },
                        color: '#94a3b8'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f1f5f9'
                    },
                    border: { display: false },
                    ticks: {
                        font: { size: 10 },
                        color: '#64748b',
                        callback: function(val) {
                            return formatBpsJs(val);
                        }
                    }
                }
            }
        }
    });
}

function updateChartData() {
    if (!tmState.chart) return;
    tmState.chart.data.labels = tmState.timeLabels;
    tmState.chart.data.datasets[0].data = tmState.downloadHistory;
    tmState.chart.data.datasets[1].data = tmState.uploadHistory;
    tmState.chart.update('none'); // Update without animation lag
}

function formatBpsJs(bps) {
    if (!bps || bps <= 0) return '0 bps';
    if (bps >= 1000000000) return (bps / 1000000000).toFixed(2) + ' Gbps';
    if (bps >= 1000000) return (bps / 1000000).toFixed(2) + ' Mbps';
    if (bps >= 1000) return (bps / 1000).toFixed(2) + ' Kbps';
    return Math.round(bps) + ' bps';
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
                  '<a href="https://wa.me/+8801540221898?text=Support" target="_blank" class="btn btn-success btn-sm" style="border-radius:20px; padding:6px 16px;">' +
                  '<i class="fa fa-whatsapp"></i> Support' +
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
