<div class="pos-tab-content active">
    <div class="row">
    	<div class="col-sm-12">
    		<ul>
    			<li>Do not refresh or leave the page while synchronizing</li>
    			<li>Timezone of POS should be same as timezone of the Miravia App</li>
    			<li>Get Miravia API details from, Miravia -> Settings -> Advance -> REST API . Enter description, select User & Provide Read/Write Permission. Click here for more info</li>
                @if(config('app.env') != 'demo' && auth()->user()->can('superadmin'))
                    <li>
                        <p>
                            To <mark>Auto Sync</mark> categories, products and orders you must setup a cron job with this command:<br/>
                            <code>{{$cron_job_command}}</code>
                        </p>
                        
                        <p>
                            Set it in cron jobs tab in cpanel or directadmin or similar panel. <br/>Or edit crontab if using cloud/dedicated hosting. <br/>Or contact hosting for help with cron job settings.
                        </p>
                    </li>
                @endif
    		</ul>
    	</div>
    </div>
</div>