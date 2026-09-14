{include file="sections/header.tpl"}

<style>
table th {
    text-transform: none !important;
    font-weight: 600 !important;
    white-space: nowrap !important;
}
</style>

<!-- Top Navigation & Action Toolbar -->
<div class="row" style="margin-bottom: 20px;">
    <div class="col-sm-12" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="javascript:history.back()" class="btn btn-sm btn-default" style="font-weight: 600; border-radius: 6px;">
                <i class="fa fa-arrow-left"></i> Back
            </a>
            <h3 style="margin: 0; font-size: 22px; font-weight: 700; color: #1e293b;">
                Daily Usage History
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
            <a class="btn btn-success btn-xs" href="{$_url}plugin/data_usage_export&start_date={$startDate}&end_date={$endDate}&router={$filterRouter}">
                <i class="fa fa-download"></i> Export CSV
            </a>
        </div>
    </div>
</div>

<!-- Stat Cards (Native Theme Style) -->
<div class="row">
    <div class="col-lg-4 col-xs-6">
        <div class="small-box bg-aqua">
            <div class="inner">
                <h4 class="text-bold" style="font-size: large;">{$rangeInFmt}</h4>
            </div>
            <div class="icon">
                <i class="ion ion-arrow-up-a"></i>
            </div>
            <a href="#" class="small-box-footer">Total Upload</a>
        </div>
    </div>
    <div class="col-lg-4 col-xs-6">
        <div class="small-box bg-green">
            <div class="inner">
                <h4 class="text-bold" style="font-size: large;">{$rangeOutFmt}</h4>
            </div>
            <div class="icon">
                <i class="ion ion-arrow-down-a"></i>
            </div>
            <a href="#" class="small-box-footer">Total Download</a>
        </div>
    </div>
    <div class="col-lg-4 col-xs-12">
        <div class="small-box bg-yellow">
            <div class="inner">
                <h4 class="text-bold" style="font-size: large;">{$rangeTotalFmt}</h4>
            </div>
            <div class="icon">
                <i class="ion ion-pie-graph"></i>
            </div>
            <a href="#" class="small-box-footer">Total Usage</a>
        </div>
    </div>
</div>

<!-- Main History Table Panel -->
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">
                Daily Breakdown
            </div>

            <!-- Main Panel Body with Integrated Filter Toolbar -->
            <div class="panel-body" style="padding: 15px;">
                <div style="margin-bottom: 15px; padding: 10px 14px; background: #f8fafc; border-radius: 6px; border: 1px solid #e2e8f0;">
                    <form method="GET" action="{$_url}plugin/data_usage_history" class="form-inline" style="display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin: 0;">
                        <input type="hidden" name="_route" value="plugin/data_usage_history">

                        <div class="form-group" style="margin: 0; display: inline-flex; align-items: center; gap: 6px;">
                            <label style="margin: 0; font-weight: 600; font-size: 12px; color: #475569;">From:</label>
                            <input type="date" name="start_date" class="form-control input-sm" value="{$startDate}" style="border-radius: 4px;">
                        </div>

                        <div class="form-group" style="margin: 0; display: inline-flex; align-items: center; gap: 6px;">
                            <label style="margin: 0; font-weight: 600; font-size: 12px; color: #475569;">To:</label>
                            <input type="date" name="end_date" class="form-control input-sm" value="{$endDate}" style="border-radius: 4px;">
                        </div>

                        <div class="form-group" style="margin: 0; display: inline-flex; align-items: center; gap: 6px;">
                            <label style="margin: 0; font-weight: 600; font-size: 12px; color: #475569;">Router:</label>
                            <select name="router" class="form-control input-sm" style="border-radius: 4px; min-width: 180px;">
                                <option value="0">All Routers ({count($routers)})</option>
                                {foreach $routers as $r}
                                <option value="{$r->id}" {if $filterRouter == $r->id}selected{/if}>{$r->name}</option>
                                {/foreach}
                            </select>
                        </div>

                        <button type="submit" class="btn btn-sm btn-primary" style="border-radius: 4px;">
                            <i class="fa fa-filter"></i> Filter
                        </button>
                        {if $filterRouter > 0 || $startDate != date('Y-m-01') || $endDate != date('Y-m-d')}
                        <a href="{$_url}plugin/data_usage_history" class="btn btn-sm btn-default" style="border-radius: 4px;">
                            <i class="fa fa-refresh"></i> Reset
                        </a>
                        {/if}
                    </form>
                </div>

                <!-- History Logs Table -->
                <div class="table-responsive">
                    <table id="datatable" class="table table-bordered table-striped table-hover" style="margin-bottom: 0; width: 100%;">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Username</th>
                                <th>Service</th>
                                <th>Router</th>
                                <th>MAC Address</th>
                                <th>Upload</th>
                                <th>Download</th>
                                <th>Total Usage</th>
                                <th style="width: 70px; text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {if count($history) == 0}
                            <tr>
                                <td colspan="9" class="text-center text-muted" style="padding: 25px;">
                                    No data usage records found for this selection.
                                </td>
                            </tr>
                            {else}
                            {foreach $history as $h}
                            <tr>
                                <td>{$h['usage_date']}</td>
                                <td>
                                    <a href="{$_url}plugin/data_usage_user&username={$h['username']|urlencode}" style="font-weight: 600;">
                                        {$h['username']}
                                    </a>
                                    {if !empty($h['fullname'])}
                                    <br><small class="text-muted">{$h['fullname']}</small>
                                    {/if}
                                </td>
                                <td>
                                    {if $h['service'] == 'PPPoE'}
                                    <span class="label label-primary">PPPoE</span>
                                    {else}
                                    <span class="label label-warning">Hotspot</span>
                                    {/if}
                                </td>
                                <td>{$h['router_name']}</td>
                                <td>
                                    <span style="font-family: monospace; font-size: 11px;">{$h['mac_address']}</span>
                                </td>
                                <td data-sort="{$h['bytes_in']}"><span class="text-info">{$h['bytes_in_fmt']}</span></td>
                                <td data-sort="{$h['bytes_out']}"><span class="text-success">{$h['bytes_out_fmt']}</span></td>
                                <td data-sort="{$h['total_bytes']}"><strong>{$h['total_bytes_fmt']}</strong></td>
                                <td class="text-center">
                                    <a href="{$_url}plugin/data_usage_user&username={$h['username']|urlencode}" class="btn btn-xs btn-info">
                                        Details
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

{include file="sections/footer.tpl"}

<script>
$(document).ready(function() {
    $('#datatable').DataTable({
        "pagingType": "full_numbers",
        "order": [[ 0, "desc" ]]
    });
});
</script>
