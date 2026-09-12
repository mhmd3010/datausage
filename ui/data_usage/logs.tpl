{include file="sections/header.tpl"}

<style>
table th, table td {
    white-space: nowrap !important;
}
table th {
    text-transform: none !important;
    font-weight: 600 !important;
}
</style>

<!-- Top Navigation & Action Toolbar -->
<div class="row" style="margin-bottom: 16px;">
    <div class="col-sm-12" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="javascript:history.back()" class="btn btn-sm btn-default" style="font-weight: 600; border-radius: 6px;">
                <i class="fa fa-arrow-left"></i> Back
            </a>
            <h3 style="margin: 0; font-size: 20px; font-weight: 700;">
                Activity Logs
            </h3>
        </div>
        <div class="btn-group" style="display: flex; align-items: center; gap: 4px;">
            <a class="btn btn-primary btn-xs" href="{$_url}plugin/data_usage_admin">
                <i class="fa fa-dashboard"></i> Dashboard
            </a>
            <a class="btn btn-warning btn-xs" href="{$_url}plugin/data_usage_scripts">
                <i class="fa fa-cogs"></i> Settings
            </a>
            <a class="btn btn-info btn-xs active" href="{$_url}plugin/data_usage_logs">
                <i class="fa fa-list-alt"></i> Logs
            </a>
            <button type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#deleteLogsModal">
                 Delete Logs
            </button>
        </div>
    </div>
</div>

<!-- Delete / Prune Logs Modal -->
<div class="modal fade" id="deleteLogsModal" tabindex="-1" role="dialog" aria-labelledby="deleteLogsModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document" style="max-width: 480px;">
        <div class="modal-content" style="border-radius: 8px; overflow: hidden; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15); border: 1px solid #e2e8f0;">
            <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 12px 18px;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="deleteLogsModalLabel" style="font-weight: 700; color: #1e293b; margin: 0;">
                     Delete Activity Logs
                </h4>
            </div>
            <form method="POST" action="{$_url}plugin/data_usage_clear_logs" id="deleteLogsForm">
                <input type="hidden" name="csrf_token" value="{$csrf_token}">
                <div class="modal-body" style="padding: 20px;">
                    <p style="font-size: 13px; color: #64748b; margin-bottom: 16px;">
                        Choose whether to prune older logs while keeping recent records, or wipe all logs permanently.
                    </p>
                    
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 14px; margin-bottom: 14px;">
                        <div class="radio" style="margin-top: 0; margin-bottom: 8px;">
                            <label style="font-weight: 600; color: #1e293b;">
                                <input type="radio" name="delete_action" id="delete_older" value="older" checked onchange="toggleDeleteDays()">
                                Keep recent logs and delete older
                            </label>
                        </div>
                        <div id="keepDaysGroup" style="margin-left: 20px; display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 12px; color: #475569;">Keep logs for:</span>
                            <input type="number" id="keep_days_input" name="keep_days" class="form-control input-sm" value="7" min="1" max="365" style="width: 75px; font-weight: 600;">
                            <span style="font-size: 12px; color: #475569;">days (delete older)</span>
                        </div>
                    </div>

                    <div style="background: #fff5f5; border: 1px solid #fecaca; border-radius: 6px; padding: 14px;">
                        <div class="radio" style="margin-top: 0; margin-bottom: 0;">
                            <label style="font-weight: 600; color: #991b1b;">
                                <input type="radio" name="delete_action" id="delete_all" value="all" onchange="toggleDeleteDays()">
                                Delete ALL logs permanently
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 12px 18px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="submitDeleteLogs()">
                         Delete Logs
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Filter Toolbar -->
<div class="panel panel-default" style="margin-bottom: 18px;">
    <div class="panel-body" style="padding: 12px 16px;">
        <form method="GET" action="{$_url}plugin/data_usage_logs" class="form-inline" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
            <input type="hidden" name="_route" value="plugin/data_usage_logs">

            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 12px; margin-right: 4px; font-weight: 600;">Router:</label>
                <select name="router_id" class="form-control input-sm" style="font-size: 11px;">
                    <option value="0">All Routers</option>
                    {foreach $routers as $r}
                    <option value="{$r->id}" {if $filterRouter == $r->id}selected{/if}>{$r->name}</option>
                    {/foreach}
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 12px; margin-right: 4px; font-weight: 600;">Event:</label>
                <select name="event_type" class="form-control input-sm" style="font-size: 11px;">
                    <option value="">All Events</option>
                    <option value="batch-sync" {if $filterEvent == 'batch-sync'}selected{/if}>Batch Sync</option>
                    <option value="session-start" {if $filterEvent == 'session-start'}selected{/if}>Connected (on-up)</option>
                    <option value="session-end" {if $filterEvent == 'session-end'}selected{/if}>Disconnected (on-down)</option>
                    <option value="single-sync" {if $filterEvent == 'single-sync'}selected{/if}>Single Sync</option>
                    <option value="cron-sync" {if $filterEvent == 'cron-sync'}selected{/if}>Cron Sync</option>
                    <option value="disconnect" {if $filterEvent == 'disconnect'}selected{/if}>Admin Disconnect</option>
                    <option value="webhook-error" {if $filterEvent == 'webhook-error'}selected{/if}>Errors</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 12px; margin-right: 4px; font-weight: 600;">Status:</label>
                <select name="status" class="form-control input-sm" style="font-size: 11px;">
                    <option value="">All Statuses</option>
                    <option value="success" {if $filterStatus == 'success'}selected{/if}>Success</option>
                    <option value="info" {if $filterStatus == 'info'}selected{/if}>Info</option>
                    <option value="warning" {if $filterStatus == 'warning'}selected{/if}>Warning</option>
                    <option value="error" {if $filterStatus == 'error'}selected{/if}>Error</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 12px; margin-right: 4px; font-weight: 600;">Search:</label>
                <input type="text" name="search" class="form-control input-sm" placeholder="User, details, IP..." value="{$search}" style="font-size: 11px;">
            </div>

            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fa fa-filter"></i> Filter
            </button>
            <a href="{$_url}plugin/data_usage_logs" class="btn btn-default btn-sm">
                <i class="fa fa-refresh"></i> Reset
            </a>
        </form>
    </div>
</div>

<!-- Logs Data Table Panel -->
<div class="panel panel-hovered mb20 panel-primary">
    <div class="panel-heading" style="display: flex; justify-content: space-between; align-items: center;">
        <span>
            System Events
        </span>
        <button type="button" class="btn btn-xs btn-default" onclick="window.location.reload();" style="border-radius: 4px;">
            <i class="fa fa-refresh"></i> Refresh
        </button>
    </div>
    <div class="panel-body">
        <div class="table-responsive">
            <table id="datatable" class="table table-bordered table-striped table-hover" style="margin-bottom: 0; width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 150px;">Timestamp</th>
                        <th style="width: 140px;">Router</th>
                        <th style="width: 130px;">User</th>
                        <th style="width: 80px; text-align: center;">Status</th>
                        <th>Details</th>
                        <th style="width: 120px;">Source IP</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $logs as $l}
                    <tr>
                        <td style="white-space: nowrap; font-weight: 600;">
                            {$l.log_time}
                        </td>
                        <td>
                            {if $l.router_name}
                                {$l.router_name}
                            {elseif $l.router_id > 0}
                                Router #{$l.router_id}
                            {else}
                                <span class="text-muted">-</span>
                            {/if}
                        </td>
                        <td>
                            {if $l.username}
                                <a href="{$_url}plugin/data_usage_user&username={$l.username|urlencode}{if $l.router_id > 0}&router_id={$l.router_id}{/if}" style="font-weight: 600;">
                                    {$l.username}
                                </a>
                            {else}
                                <span class="text-muted">-</span>
                            {/if}
                        </td>
                        <td style="text-align: center;">
                            {if $l.status == 'success'}
                                <span class="label label-success">Success</span>
                            {elseif $l.status == 'warning'}
                                <span class="label label-warning">Warning</span>
                            {elseif $l.status == 'error'}
                                <span class="label label-danger">Error</span>
                            {else}
                                <span class="label label-info">Info</span>
                            {/if}
                        </td>
                        <td>
                            {$l.details}
                        </td>
                        <td>
                            {if $l.ip_address}
                                <span style="font-family: monospace;">{$l.ip_address}</span>
                            {else}
                                <span class="text-muted">-</span>
                            {/if}
                        </td>
                    </tr>
                    {foreachelse}
                    <tr>
                        <td colspan="6" class="text-center text-muted" style="padding: 24px;">
                            No log records found.
                        </td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}

<script>
$(document).ready(function() {
    if (!$('#du_footer_brand').length) {
        $('.main-footer').append('<span id="du_footer_brand"> | <b>Data Usage</b> by <a href="https://linktr.ee/systik" target="_blank" rel="noopener noreferrer">Mahmud</a></span>');
    }
    $('#datatable').DataTable({
        "pagingType": "full_numbers",
        "order": [[ 0, "desc" ]],
        "pageLength": 25,
        "responsive": true
    });
});

function toggleDeleteDays() {
    var isOlder = document.getElementById('delete_older').checked;
    var keepGroup = document.getElementById('keepDaysGroup');
    var keepInput = document.getElementById('keep_days_input');
    if (isOlder) {
        keepGroup.style.opacity = '1';
        keepInput.disabled = false;
    } else {
        keepGroup.style.opacity = '0.5';
        keepInput.disabled = true;
    }
}

function submitDeleteLogs() {
    var isOlder = document.getElementById('delete_older').checked;
    var keepInput = document.getElementById('keep_days_input');
    var form = document.getElementById('deleteLogsForm');
    
    if (isOlder) {
        var days = parseInt(keepInput.value, 10);
        if (isNaN(days) || days < 1) {
            alert('Please enter a valid number of days.');
            return;
        }
        form.submit();
    } else {
        var promptMsg = 'Are you sure you want to permanently delete ALL activity logs?';
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Delete All Logs?',
                text: 'This will permanently wipe all logs and cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Delete All'
            }).then(function(result) {
                if (result.isConfirmed) {
                    keepInput.disabled = false;
                    keepInput.value = '0';
                    form.submit();
                }
            });
        } else {
            if (confirm(promptMsg)) {
                keepInput.disabled = false;
                keepInput.value = '0';
                form.submit();
            }
        }
    }
}
</script>
