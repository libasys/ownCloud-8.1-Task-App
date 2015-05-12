<h3><?php p($l->t('Active Calendars')); ?></h3>
	<ul id="calendarList">
		
	<?php 
	   $mySharees=OCA\Calendar\Object::getCalendarSharees();
	    $activeCal=OCP\Config::getUserValue(OCP\USER::getUser(), 'calendar', 'choosencalendar');
	   foreach($_['calendars'] as $calInfo){
	     if($calInfo['issubscribe'] == false){	
		    $rightsOutput='';
			$share='';
			$isActiveUserCal='';
			 $notice='';
			 if($activeCal === $calInfo['id']){
			 	$isActiveUserCal='isActiveCal';
			 }
			 
			 if((is_array($mySharees) && array_key_exists($calInfo['id'], $mySharees))) {
			 	$sharedescr=$mySharees[$calInfo['id']];	
			 	$share='<i class="ioc ioc-share toolTip" title="<b>'.  \OCA\Calendar\App::$l10n->t('Shared with').'</b><br>'.$sharedescr.'"></i>'; 	
			 }
			 
			$displayName='<span class="descr">'.$calInfo['displayname'].' '.$share.'</span>';
			
	         if($calInfo['userid'] != OCP\USER::getUser()){
	  	       
	         	if(\OCP\Share::getItemSharedWithByLink('calendar','calendar-'.$calInfo['id'],$calInfo['userid'])){
	         		$notice='<b>Notice</b><br>'.(string)$l->t('This calendar is also shared by Link for public!').'<br>';
	         	}
				
			    $rightsOutput=OCA\Calendar\Calendar::permissionReader($calInfo['permissions']);
	  	        $displayName='<span class="toolTip descr" title="'.$notice.(string)$l->t('Calendar').' '.$calInfo['displayname'].'<br />('.$rightsOutput.')">'.$calInfo['displayname'].' (' . OCA\Calendar\App::$l10n->t('by') . ' ' .$calInfo['userid'].')</span>';
	        }
			 $countCalEvents=0;
			 if(array_key_exists($calInfo['id'],$_['aCountCalEvents'])) $countCalEvents=$_['aCountCalEvents'][(string)$calInfo['id']];
			
			 
		   	print_unescaped('<li class="calListen '.$isActiveUserCal.'" data-permissions="'.$calInfo['permissions'].'" data-id="'.$calInfo['id'].'"><span class="colCal" style="background-color:'.$calInfo['calendarcolor'].';color:'.\OCA\Calendar\Calendar::generateTextColor($calInfo['calendarcolor']).';">'.substr($calInfo['displayname'],0,1).'</span> '.$displayName.'<span class="iCount">'.$countCalEvents.'</span></li>');
	     }
	   }
	   
	   
	 ?>
	 </ul>
	 <br style="clear:both;" /><br />
	 <h3><label id="lTimeline"><i style="font-size:22px;" class="ioc ioc-chevron-down ioc-rotate-270"></i>&nbsp;<?php p($l->t('Timelined')); ?> <?php p($l->t('Tasks')); ?></label></h3>
	 <div data-id="lTimelineHolder" style="padding:0;background:none;">
		 <ul id="taskstime">
		 	<li class="taskstimerow" data-id="today" title="<?php p($l->t('Tasks')); ?>  <?php p($l->t('on')); ?> <?php p($_['aTaskTime']['today']); ?>"><span class="descr"><?php p($l->t('Tasks')); ?> <?php p($l->t('today')); ?></span><span class="iCount"><?php p($_['tasksCount']['today']); ?></span></li>
		 	<li class="taskstimerow" data-id="tomorrow" title="<?php p($l->t('Tasks')); ?>  <?php p($l->t('on')); ?> <?php p($_['aTaskTime']['tomorrow']); ?>"><span class="descr"><?php p($l->t('Tasks')); ?>  <?php p($l->t('tomorrow')); ?></span><span class="iCount"><?php p($_['tasksCount']['tomorrow']); ?></span></li>
		 	<li class="taskstimerow" data-id="actweek" title="<?php p($l->t('Tasks')); ?>   <?php p($_['aTaskTime']['actweek']); ?>"><span class="descr"><?php p($l->t('This Week')); ?></span><span class="iCount"><?php p($_['tasksCount']['actweek']); ?></span></li>
	       	<li class="taskstimerow" data-id="comingsoon" title="<?php p($l->t('Coming soon')); ?>"><span class="descr"><?php p($l->t('Coming soon')); ?> </span><span class="iCount"><?php p($_['tasksCount']['comingsoon']); ?></span></li>
		 	<li class="taskstimerow" data-id="withoutdate" title="<?php p($l->t('Tasks')); ?>  <?php p($l->t('Without Time')); ?>"><span class="descr"><?php p($l->t('Without Time')); ?></span><span class="iCount"><?php p($_['tasksCount']['withoutdate']); ?></span></li>
		 	<li class="taskstimerow" data-id="missedactweek" title="<?php p($l->t('Missed')); ?> <?php p($l->t('Tasks')); ?>"><span class="descr"><?php p($l->t('Missed')); ?> <?php p($l->t('Tasks')); ?></span><span class="iCount"><?php p($_['tasksCount']['missedactweek']); ?></span></li>
		 	</ul>
		 <br style="clear:both;" /><br />
		  <ul id="taskssum">
		 	<li class="taskstimerow" data-id="showall" title="<?php p($l->t('All')); ?> <?php p($l->t('Tasks')); ?> "><span class="descr"><?php p($l->t('All')); ?> <?php p($l->t('Tasks')); ?> </span><span class="iCount"><?php p($_['tasksCount']['alltasks']); ?></span></li>
		 	<li class="taskstimerow" data-id="alltasksdone" title="<?php p($l->t('Completed')); ?> <?php p($l->t('Tasks')); ?>"><span class="descr"><?php p($l->t('Completed')); ?> <?php p($l->t('Tasks')); ?> </span><span class="iCount"><?php p($_['tasksCount']['alltasksdone']); ?></span></li>
		 	<li class="taskstimerow" data-id="sharedtasks" title="<?php p($l->t('Shared')); ?> <?php p($l->t('Tasks')); ?>"><span class="descr"><?php p($l->t('Shared')); ?> <?php p($l->t('Tasks')); ?> </span><span class="iCount"><?php p($_['tasksCount']['sharedtasks']); ?></span></li>
	
		 </ul>
	     <br style="clear:both;" />
    </div>
	 <h3 ><label id="showCategory"><i style="font-size:22px;" class="ioc ioc-chevron-down ioc-rotate-270"></i>&nbsp;<?php p($l->t('Category')); ?></label></h3>
	 <ul id="categoryTasksList">
	 </ul>
	
		 <h3 style="clear:both;"><label id="lCalendar"><i style="font-size:22px;" class="ioc ioc-chevron-down"></i>&nbsp;<?php p($l->t('Select Day')); ?></label></h3>
     <div id="datepickerNav" style="padding:0;background:none;margin-left:auto;margin-right:auto;">
     </div>
</div>