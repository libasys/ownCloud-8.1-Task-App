/**
 * ownCloud - Aufgaben Remastered
 *
 * @author Sebastian Doell
 * @copyright 2013 sebastian doell sebastian@libasys.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

OC.Aufgaben = {
	firstLoading : true,
	startMode : 'actweek',
	categories:null,
	tags:null,
	mycalendars:null,
	sendmail : function(eventId, emails) {
		//Calendar.UI.loading(true);
		$.post(OC.filePath('calendar', 'ajax/event', 'sendmail.php'), {
			eventId : eventId,
			emails : emails,

		}, function(result) {
			if (result.status == 'success') {
				OC.dialogs.alert('E-Mails an: ' + emails + ' erfolgreich versendet.', 'Email erfolgreich versendet');
				$('#inviteEmails').val('');
			} else {
				OC.dialogs.alert(result.data.message, 'Error sending mail');
			}
			Calendar.UI.loading(false);
		});
	},
	getReminderonSubmit : function() {
		var sAdvMode = $('#reminderAdvanced option:selected').val();
		var sResult = '';
		if (sAdvMode == 'DISPLAY') {
			var sTimeMode = $('#remindertimeselect option:selected').val();
			//-PT5M
			var rTimeSelect = $('#remindertimeinput').val();

			if (sTimeMode != 'ondate' && (Math.floor(rTimeSelect) == rTimeSelect && $.isNumeric(rTimeSelect))) {
				var sTimeInput = $('#remindertimeinput').val();
				if (sTimeMode == 'minutesbefore') {
					sResult = '-PT' + sTimeInput + 'M';
				}
				if (sTimeMode == 'hoursbefore') {
					sResult = '-PT' + sTimeInput + 'H';
				}
				if (sTimeMode == 'daysbefore') {
					sResult = '-PT' + sTimeInput + 'D';
				}
				if (sTimeMode == 'minutesafter') {
					sResult = '+PT' + sTimeInput + 'M';
				}
				if (sTimeMode == 'hoursafter') {
					sResult = '+PT' + sTimeInput + 'H';
				}
				if (sTimeMode == 'daysafter') {
					sResult = '+PT' + sTimeInput + 'D';
				}
				sResult = 'TRIGGER:' + sResult;
			}
			if (sTimeMode == 'ondate' && $('#reminderdate').val() != '') {
				//20140416T065000Z
				dateTuple = $('#reminderdate').val().split('-');
				timeTuple = $('#remindertime').val().split(':');

				var day, month, year, minute, hour;
				day = dateTuple[0];
				month = dateTuple[1];
				year = dateTuple[2];
				hour = timeTuple[0];
				minute = timeTuple[1];

				var sDate = year + '' + month + '' + day + 'T' + hour + '' + minute + '00Z';

				sResult = 'TRIGGER;VALUE=DATE-TIME:' + sDate;
			}
			if (sResult != '') {
				$("#sReminderRequest").val(sResult);
				var sReader = OC.Aufgaben.reminderToText(sResult);
				$('#reminderoutput').text(sReader);

			} else {
				OC.Aufgaben.reminder('reminderreset');
				alert('Wrong Input!');
			}
		}
		//alert(sResult);

	},
	reminderToText : function(sReminder) {
		if (sReminder != '') {
			var sReminderTxt = '';
			if (sReminder.indexOf('-PT') != -1) {
				//before
				var sTemp = sReminder.split('-PT');
				var sTempTF = sTemp[1].substring((sTemp[1].length - 1));
				if (sTempTF == 'M') {
					sReminderTxt = t('calendar', 'Minutes before');
				}
				if (sTempTF == 'H') {
					sReminderTxt = t('calendar', 'Hours before');
				}
				if (sTempTF == 'D') {
					sReminderTxt = t('calendar', 'Days before');
				}
				var sTime = sTemp[1].substring(0, (sTemp[1].length - 1));
				sReminderTxt = sTime + ' ' + sReminderTxt;
			} else if (sReminder.indexOf('+PT') != -1) {
				var sTemp = sReminder.split('+PT');
				var sTempTF = sTemp[1].substring((sTemp[1].length - 1));
				if (sTempTF == 'M') {
					sReminderTxt = t('calendar', 'Minutes after');
				}
				if (sTempTF == 'H') {
					sReminderTxt = t('calendar', 'Hours after');
				}
				if (sTempTF == 'D') {
					sReminderTxt = t('calendar', 'Days after');
				}
				var sTime = sTemp[1].substring(0, (sTemp[1].length - 1));
				sReminderTxt = sTime + ' ' + sReminderTxt;
			} else {
				//onDate
				sReminderTxt = t('calendar', 'on');
				var sTemp = sReminder.split('DATE-TIME:');
				sDateTime = sTemp[1].split('T');
				sYear = sDateTime[0].substring(0, 4);
				sMonth = sDateTime[0].substring(4, 6);
				sDay = sDateTime[0].substring(6, 8);
				sHour = sDateTime[1].substring(0, 2);
				sMinute = sDateTime[1].substring(2, 4);
				sTempDate = new Date();
				//today=sTempDate.toDateString();
				today = $.datepicker.formatDate('@', new Date(sTempDate.getFullYear() + ',' + sTempDate.getDate() + ',' + (sTempDate.getMonth() + 1)));
				tomorrow = $.datepicker.formatDate('@', new Date(sTempDate.getFullYear() + ',' + (sTempDate.getDate() + 1) + ',' + (sTempDate.getMonth() + 1)));

				aktDate = $.datepicker.formatDate('@', new Date(sYear + ',' + sDay + ',' + sMonth));
				sReminderTxt = sReminderTxt + ' ' + sDay + '.' + sMonth + '.' + sYear + ' ' + sHour + ':' + sMinute;
				if (aktDate == today) {
					sReminderTxt = t('aufgaben', 'today') + ' ' + sHour + ':' + sMinute;
				}

				if (aktDate == tomorrow) {
					sReminderTxt = t('aufgaben', 'tomorrow') + ' ' + sHour + ':' + sMinute;
				}

			}

			return sReminderTxt;
		} else
			return false;
	},

	getReminderSelectLists : function() {
		//INIT
		var sCalendarSel = '#sCalSelect.combobox';
		$(sCalendarSel + ' ul').hide();
		if ($(sCalendarSel + ' li').hasClass('isSelected')) {
			$(sCalendarSel + ' .selector').html('<span class="colCal" style="cursor:pointer;float:none;margin-top:5px;background-color:' + $(sCalendarSel + ' li.isSelected').data('color') + '">&nbsp;<span>');
		}
		$(sCalendarSel + ' .selector').on('click', function() {
			if ($(sCalendarSel + ' ul').is(':visible')) {
				$(sCalendarSel + ' ul').hide('fast');
			} else {
				$(sCalendarSel + ' ul').show('fast');
			}

		});
		$(sCalendarSel + ' li').click(function() {
			$(this).parents(sCalendarSel).find('.selector').html('<span class="colCal" style="float:none;margin-top:5px;background-color:' + $(this).data('color') + '">&nbsp;<span>');
			$(sCalendarSel + ' li .colCal').removeClass('isSelectedCheckbox');
			$(sCalendarSel + ' li').removeClass('isSelected');
			$('#hiddenCalSelection').val($(this).data('id'));
			$(this).addClass('isSelected');
			$(this).find('.colCal').addClass('isSelectedCheckbox');
			$(sCalendarSel + ' ul').hide();
		});
		//ENDE

		//sRepeatSelect
		var sReminderSel = '#sReminderSelect.combobox';
		$(sReminderSel + ' ul').hide();
		$('#showOwnReminderDev').hide();

		if ($(sReminderSel + ' li').hasClass('isSelected')) {
			$(sReminderSel + ' .selector').html($(sReminderSel + ' li.isSelected').text());
			if ($(sReminderSel + ' li.isSelected').data('id') != 'OWNDEF') {
				$('#reminderoutput').hide();
			}
		}
		$(sReminderSel + ' .comboSelHolder').on('click', function() {
			$(sReminderSel + ' ul').toggle();
		});
		$(sReminderSel + ' li').click(function() {
			$(sReminderSel + ' li .colCal').removeClass('isSelectedCheckbox');
			$(sReminderSel + ' li').removeClass('isSelected');
			$('#reminder').val($(this).data('id'));
			if ($(this).data('id') == 'OWNDEF') {
				$('#showOwnReminderDev').show();
				$('#reminderoutput').show();
			} else {
				$('#sReminderRequest').val('TRIGGER:' + $(this).data('id'));
				$('#reminderoutput').hide();
			}
			if ($(this).data('id') == 'none') {
				$('#reminderoutput').hide();
			}
			$(this).addClass('isSelected');
			$(this).parents(sReminderSel).find('.selector').html($(this).text());
			$(this).find('.colCal').addClass('isSelectedCheckbox');
			$(sReminderSel + ' ul').hide();
		});

		//Reminder
		$('#reminderdate').datetimepicker({
			altField : '#remindertime',
			dateFormat : 'dd-mm-yy',
			stepMinute : 5,
			numberOfMonths : 1,
			addSliderAccess : true,
			sliderAccessArgs : {
				touchonly : false
			},
			showButtonPanel : false
		});

		OC.Aufgaben.reminder('init');
		$('#reminderAdvanced').change(function() {
			OC.Aufgaben.reminder('reminder');
		});
		$('#remindertimeselect').change(function() {
			OC.Aufgaben.reminder('remindertime');
		});

	},
	taskRendering : function(taskSingleArray) {
		if ( typeof taskSingleArray.id == 'undefined') {
			//alert('found');
		}
		if ( typeof taskSingleArray.id != 'undefined') {
			var SubClass = '';
			if (taskSingleArray.relatedto != '') {
				SubClass = ' subtask';
			}

			var tmpTask = $('<div class="task dropzone' + SubClass + '"  data-id="' + taskSingleArray.id + '" data-uid="' + taskSingleArray.eventuid + '" data-relatedto="' + taskSingleArray.relatedto + '">');

			if (taskSingleArray.orgevent) {
				tmpTask = $('<div class="task dropzone" style="border:2px dotted #000;" data-id="' + taskSingleArray.id + '">');
			}

			if (taskSingleArray.relatedto != '' && $('div[data-uid="' + taskSingleArray.relatedto + '"]').length > 0) {
				$('div[data-uid="' + taskSingleArray.relatedto + '"]').append(tmpTask);

			} else {
				if ($('#newtodo').length > 0) {
					$('.task[id="newtodo"]').before(tmpTask);
				} else {
					$('#tasks_list').append(tmpTask);
				}
			}

			var checkbox = $('<input type="checkbox" class="regular-checkbox" id="chk_' + taskSingleArray.id + '"><label for="chk_' + taskSingleArray.id + '"></label>');

			if (taskSingleArray.iscompleted) {
				checkbox.attr('checked', 'checked');
				tmpTask.addClass('done');
			} else {
				tmpTask.addClass('opentask');
			}

			var DivCompleted = $('<div>').addClass('completed');
			DivCompleted.append(checkbox);

			DivCompleted.prependTo(tmpTask);

			if (taskSingleArray.permissions & OC.PERMISSION_UPDATE) {
				$('#chk_' + taskSingleArray.id).on('click', OC.Aufgaben.completedHandler);
			} else {
				checkbox.attr('disabled', 'disabled');
			}

			if (taskSingleArray.subtask) {

				$('<div style="float:left;margin-top:3px;width:30px;text-align:center;">').append($('<i id="arrow_' + taskSingleArray.id + '">').addClass('ioc ioc-chevron-down ioc-rotate-270').css('font-size', '20px')).appendTo(tmpTask);
				$('#arrow_' + taskSingleArray.id).on('click', OC.Aufgaben.ToggleView);
			}

			$('<div>').addClass('colCal').css({
				'background-color' : taskSingleArray.bgcolor,
				'color' : taskSingleArray.color
			}).html(taskSingleArray.displayname.substring(0, 1)).appendTo(tmpTask);

			//Div for the add Icons

			var priority = taskSingleArray.priority;
			if (priority != '') {
				$('<div>').addClass('ioc ioc-flash priority priority-' + ( priority ? priority : 'n')).appendTo(tmpTask);
			}

			var iconDiv = $('<div>').addClass('icons');
			iconDiv.appendTo(tmpTask);

			var imgShare = '';
			if (taskSingleArray.shared) {
				imgShare = ' <i class="ioc ioc-share" title="' + t('core', 'Shared') + '"></i>&nbsp; ';
				$(imgShare).appendTo(iconDiv);
			}

			var imgPrivate = '';

			if (taskSingleArray.privat == 'private') {
				imgPrivate = ' <i class="ioc ioc-lock" title="private"></i> ';
				$(imgPrivate).appendTo(iconDiv);
			}

			if (taskSingleArray.privat == 'confidential') {
				imgPrivate = ' <i class="ioc ioc-eye" title="confidential"></i> ';
				$(imgPrivate).appendTo(iconDiv);
			}

			var repeatDescr = '';
			if (taskSingleArray.repeating) {
				if (taskSingleArray.day != undefined) {
					repeatDescr = ' taeglich -> ' + taskSingleArray.day;
				}
			}
			$('<div>').addClass('summary').text(taskSingleArray.summary + repeatDescr).on('click', OC.Aufgaben.showEditTask).appendTo(tmpTask);
			//summary

			var cpDate = '';
			if (taskSingleArray.iscompleted) {
				cpDate = taskSingleArray.completed;
			}
			$('<div>').addClass('completeDate').text(cpDate).appendTo(tmpTask);
			//Categories
			if (taskSingleArray.relatedto == '') {

				var SubTaskHandler = $('<i id="newsubtask_' + taskSingleArray.id + '"/>').attr('title',t('aufgaben','Add Subtask')).addClass('ioc ioc-add').css({
					'cursor' : 'pointer',
					'font-size' : '20px',
					'float' : 'right',
					'margin-right' : '50px',
					'margin-top' : '15px'
				});
				SubTaskHandler.appendTo(tmpTask);
				$('#newsubtask_' + taskSingleArray.id).on('click', OC.Aufgaben.newTask);

				var TaskCompleteHandler = $('<i id="newcomplete_' + taskSingleArray.id + '"/>').attr('title',t('aufgaben','Calculate Complete Main Task')).addClass('ioc ioc-refresh').css({
					'cursor' : 'pointer',
					'font-size' : '20px',
					'float' : 'right',
					'margin-right' : '10px',
					'margin-top' : '15px'
				});
				TaskCompleteHandler.appendTo(tmpTask);
				$('#newcomplete_' + taskSingleArray.id).on('click', OC.Aufgaben.newCompleteCalc);

			}
			var $categories = $('<div>').addClass('categories').appendTo(tmpTask);

			$(taskSingleArray.categories).each(function(i, category) {
				bgcolor = '#ccc';
				color = '#000';
				if (OC.Aufgaben.tags[category] != undefined) {
					bgcolor = OC.Aufgaben.tags[category]['bgcolor'];
					color = OC.Aufgaben.tags[category]['color'];
				}

				$categories.append($('<a>').addClass('tag').css({
					'background-color' : bgcolor,
					'color' : color
				}).attr('title', category).text(category.substring(0, 1)).on('click', OC.Aufgaben.filterCategoryHandler));
			});

			if (taskSingleArray.due || taskSingleArray.startdate) {
				$('<br style="clear:both;">').appendTo(tmpTask);
				sAlarm = '';
				if (taskSingleArray.sAlarm) {
					sAlarm = '(' + t('aufgaben', 'Reminder') + ' ';
					$.each(taskSingleArray.sAlarm, function(i, el) {
						sAlarm += ' ' + OC.Aufgaben.reminderToText(el);
					});
					sAlarm += ')';
				}
				var sStart = '';
				if (taskSingleArray.startdate) {
					sStart = t('aufgaben', 'Start') + ' ' + taskSingleArray.startdate + ' ';
				}
				var sDue = '';
				if (taskSingleArray.due) {
					sDue = t('aufgaben', 'Due') + ' ' + taskSingleArray.due + ' ';
				}
				$('<div>').addClass('dueDate').text(sStart + sDue + ' ' + sAlarm).appendTo(tmpTask);
			}

			//  if (taskSingleArray.complete > 0) {
			if (!taskSingleArray.due && !taskSingleArray.startdate) {
				$('<br style="clear:both;">').appendTo(tmpTask);
			}
			var complPercent = taskSingleArray.complete;
			if (taskSingleArray.iscompleted) {
				complPercent = 100;
			}

			var MainTaskClass = '';
			if (taskSingleArray.relatedto == '') {
				MainTaskClass = 'maintask ';
			}
			//$('<br style="clear:both;">').appendTo(tmpTask);
			$('<div>').addClass('completeLine').html('<div data-width="' + complPercent + '" title="' + complPercent + '% Completed" class="' + MainTaskClass + 'completeActual bgcolor-' + complPercent + '" style="width:' + complPercent + '%"></div>').appendTo(tmpTask);
			//  }
		}
	},
	filterCategoryHandler : function(event) {
		$Task = $(this).closest('.task');
		if ($Task.hasClass('filterActive')) {
			$Task.removeClass('filterActive');
			$('.task').each(function(i, el) {
				$(el).show('fast');
			});
		} else {
			$('.task [data-val="counterdone"]').addClass('arrowDown');
			OC.Aufgaben.filter($(this).attr('title'));
		}
	},
	addCategory : function(iId, category) {
		
		$.ajax({
			type : 'POST',
			url : OC.generateUrl('apps/aufgaben/addcategorytask'),
			data :{
				id : iId,
				category : category
			},
			success : function(jsondata) {
				myMode = $('#donetodo').data('mode');
				myCal = $('#donetodo').data('cal');

				if (myMode == 'calendar') {
					OC.Aufgaben.updateList(myCal);
				}

				if (myMode != 'calendar') {
					OC.Aufgaben.updateListByPeriod(myMode);
				}
			}
		});
		
	},
	generateTaskList : function(jsondata) {
		$('#donetodo').on('click', function() {
			$('.task [data-val="counterdone"]').toggleClass('arrowDown');
			if (! $('.task.done').is(':visible')) {
				$('span[data-val="counterdone"]:before').css('content', '\25BC');
				$('.task.done').show('fast');
			} else {
				$('.task.done').hide('fast');
			}
		});

		$('.task [data-val="counterdone"]').text($(jsondata['done']).length);
		$(jsondata['done']).each(function(i, task) {
			OC.Aufgaben.taskRendering(task);
		});

		$(jsondata['open']).each(function(i, task) {
			OC.Aufgaben.taskRendering(task);
		});
		if ($(jsondata['done']).length == 0 && $(jsondata['open']).length == 0) {
			var tmpTask = $('<div class="task" data-id="notodo">').html('<label>' + t('aufgaben', 'No Todos') + '</label>');
			$('#tasks_list').append(tmpTask);
		}
		$('.task .subtask').toggle();

		$(".dropzone").droppable({
			activeClass : "activeHover",
			hoverClass : "dropHover",
			accept : '#categoryTasksList .categorieslisting',
			over : function(event, ui) {

			},
			drop : function(event, ui) {
				if ($(this).data('id') != null)
					OC.Aufgaben.addCategory($(this).data('id'), ui.draggable.attr('title'));
			}
		});

		if (OC.Aufgaben.firstLoading == true) {
			OC.Aufgaben.checkShowEventHash();
			OC.Aufgaben.firstLoading = false;
			OC.Aufgaben.calcDimension();
		}

	},
	initActionHandler : function() {

		$( "#accordion" ).accordion({
		      collapsible: true,
		      heightStyle: "content",
		      active: false,
		      animate:false
		    });
		    
		

		$('.toolTip').tipsy({
			html : true,
			gravity : $.fn.tipsy.autoNS
		});

		$('#sWV').datepicker({
			dateFormat : "dd.mm.yy",
			minDate : null
		});
		$('#sWV_time').timepicker({
			showPeriodLabels : false,
			showButtonPanel : false
		});

		$('#startdate').datepicker({
			dateFormat : "dd.mm.yy",
			minDate : null
		});
		$('#startdate_time').timepicker({
			showPeriodLabels : false,
			showButtonPanel : false
		});
		//$('#ldatetime')
		var sDateTimeText='';
		if($('#startdate').val()!=''){
			sDateTimeText+=t('aufgaben','Start')+' '+ $('#startdate').val();
		}
		if($('#startdate_time').val()!=''){
			sDateTimeText+= ' '+$('#startdate_time').val();
		}
		if($('#sWV').val()!=''){
			sDateTimeText+=' '+t('aufgaben','Due')+' '+ $('#sWV').val();
		}
		if($('#sWV_time').val()!=''){
			sDateTimeText+= ' '+$('#sWV_time').val();
		}
		if(sDateTimeText !=''){
			$('#ldatetime').text(sDateTimeText);
		}
		
		$('#accordion span.ioc-checkmark').hide();
				if($('#taskForm input[name="link"]').val() != ''){
					$('#accordion span.lurl').show();
				}
				if($('#taskForm textarea[name="noticetxt"]').val() != ''){
					$('#accordion span.lnotice').show();
				}
				if($('#taskForm input[name="taskcategories"]').val() != ''){
					$('#accordion span.ltag').show();
				}
				
		//Tagsmanager
		aExitsTags = false;
		if ($('#taskcategories').val() != '') {
			var sExistTags = $('#taskcategories').val();
			var aExitsTags = sExistTags.split(",");
		}

		$('#tagmanager').tagit({
			tagSource : OC.Aufgaben.categories,
			maxTags : 4,
			initialTags : aExitsTags,
			allowNewTags : false,
			placeholder : t('calendar', 'Add Tags'),
		});

		//Init Slider Complete
		$('#percentVal').text($("#percCompl").val() + '%');
		$("#slider").slider({
			value : $("#percCompl").val(),
			range : "min",
			min : 0,
			max : 100,
			step : 1,
			slide : function(event, ui) {
				$("#percCompl").val(ui.value);
				$('#percentVal').text(ui.value + '%');
			}
		});

		//Init Slider Priority
		$('#prioVal').text($("#priority").val());
		$("#sliderPriority").slider({
			value : $("#priority").val(),
			range : "min",
			min : 0,
			max : 9,
			step : 1,
			slide : function(event, ui) {
				$("#priority").val(ui.value);
				$('#prioVal').text(ui.value);
			}
		});
		//Init

		var aAccess = {
			'PUBLIC' : {
				'val' : 1,
				'color' : '#8ae234',
				'txt' : t('calendar', 'Show full event')
			},
			'CONFIDENTIAL' : {
				'val' : 2,
				'color' : '#FBDD52',
				'txt' : t('calendar', 'Busy')
			},
			'PRIVATE' : {
				'val' : 3,
				'color' : '#D9534F',
				'txt' : t('calendar', 'Hide event')
			}
		};

		var initVal = aAccess[$('#accessclass').val()]['val'];

		$('#showAsVal').text(aAccess[$('#accessclass').val()]['txt']);
		// Init Access Class
		$("#sliderShowAs").slider({
			range : "min",
			value : initVal,
			min : 0,
			max : 3,
			step : 1,
			change : function(event, ui) {
				var color = '#8ae234';
				var sText = 'frei';
				var sAccess = 'PUBLIC';
				if (ui.value == 0) {
					$("#sliderShowAs").slider('value', 1);
					color = aAccess['PUBLIC']['color'];
					sText = aAccess['PUBLIC']['txt'];
					sAccess = 'PUBLIC';
				}
				if (ui.value == 1) {
					color = aAccess['PUBLIC']['color'];
					sText = aAccess['PUBLIC']['txt'];
					sAccess = 'PUBLIC';
				}
				if (ui.value == 2) {
					color = aAccess['CONFIDENTIAL']['color'];
					sText = aAccess['CONFIDENTIAL']['txt'];
					sAccess = 'CONFIDENTIAL';
				}
				if (ui.value == 3) {
					color = aAccess['PRIVATE']['color'];
					sText = aAccess['PRIVATE']['txt'];
					sAccess = 'PRIVATE';
				}

				$("#sliderShowAs .ui-widget-header").css('background', color);
				$('#showAsVal').text(sText);
				$('#accessclass').val(sAccess);

				//$( "#percCompl" ).val(ui.value );
			}
		});
		$("#sliderShowAs .ui-widget-header").css('background', aAccess[$('#accessclass').val()]['color']);

	},
	showEditTask : function(event) {
		if (!event['id']) {
			$Task = $(this).closest('.task');
			TaskId = $Task.attr('data-id');
			myMode = $('#donetodo').data('mode');
			myCal = $('#donetodo').data('cal');
			//Find Main Id

		} else {

			TaskId = event['id'];
			myMode = 'showall';
			myCal = 0;
			OC.Aufgaben.updateListByPeriod(myMode);
			$('.taskstimerow').removeClass('active');
			$('.taskstimerow[data-id="showall"]').addClass('active');

		}
		
		
		$.ajax({
			type : 'POST',
			url :OC.generateUrl('apps/aufgaben/edittask'),
			data : {
				tid : TaskId,
				mytaskmode : myMode,
				mytaskcal : myCal
			},
			success : function(data) {
				$('.task').removeClass('highlightTask');
				$('.task[data-id="' + TaskId + '"]').addClass('highlightTask');

				$("#dialogmore").html(data);
				//$("#tasks_list_details").show('fast');
				$("#dialogmore").dialog({
					resizable : false,
					title : t('aufgaben', 'Edit Task'),
					width : 370,
					beforeClose: function( event, ui ) {
						if(OC.Share.droppedDown){
							OC.Share.hideDropDown();
						}
					},
					position : {
						my : 'center center',
						at : 'center center',
						of : $('#app-content')
					},
					modal : false,
				});

				$('#showOnShare').hide();

			
				$('#editTodo-submit').on('click', function() {
					if ($('#tasksummary').val() != '') {
						OC.Aufgaben.SubmitForm('edititTask', '#taskForm', '#tasks_list_details');
						$('.task[data-id="' + TaskId + '"]').addClass('highlightTask');
					} else {
						OC.Aufgaben.showMeldung(t('aufgaben', 'Title is missing'));
					}
				});
				$('#editTodo-cancel').on('click', function() {
					$("#dialogmore").dialog("close");
					$('.task[data-id="' + TaskId + '"]').removeClass('highlightTask');
				});
				$('#deleteTodo-submit').on('click', function() {
					taskId = $('#taskid').val();
					OC.Aufgaben.deleteHandler(taskId);
					$('.task[data-id="' + TaskId + '"]').removeClass('highlightTask');
				});

				OC.Aufgaben.getReminderSelectLists();
				if ($('#sReminderRequest').val() != '') {
					$('#reminderoutput').text(OC.Aufgaben.reminderToText($('#sReminderRequest').val()));
					$('#lreminder').html('<i class="ioc ioc-clock" style="font-size:14px;"></i> '+OC.Aufgaben.reminderToText($('#sReminderRequest').val()));
				}
				
				OC.Aufgaben.initActionHandler();

				OC.Share.loadIcons('todo', '');

			}
		});
		return false;
	},

	editHandler : function(event) {
		$Task = $(this).closest('.task');
		TaskId = $Task.attr('data-id');

		OC.Aufgaben.showEditTask(TaskId);

	},
	addSharedHandler : function(event) {
		$Task = $(this).closest('.task');
		TaskId = $Task.attr('data-id');

		OC.Aufgaben.openShareDialog(TaskId);
	},
	ToggleView : function() {
		$Task = $(this).closest('.task');
		TaskId = $Task.attr('data-id');
		if ($('div[data-id="' + TaskId + '"]').find('i.ioc-chevron-down').hasClass('ioc-rotate-270')) {
			$('div[data-id="' + TaskId + '"]').find('i.ioc-chevron-down').removeClass('ioc-rotate-270');
			$('div[data-id="' + TaskId + '"]').find('.subtask').show();
		} else {
			$('div[data-id="' + TaskId + '"]').find('i.ioc-chevron-down').addClass('ioc-rotate-270');
			$('div[data-id="' + TaskId + '"]').find('.subtask').hide();
		}

	},
	renderComplete : function(taskdata) {
		$task = $('div[data-id="' + taskdata.id + '"]');
		myClone = $task.clone(true);
		if (taskdata.completed) {

			myClone.addClass('done').css('display', 'none');
			myClone.removeClass('opentask');
			myClone.find('#chk_' + taskdata.id).attr('checked', 'checked');
			myClone.find('.completeDate').text($.datepicker.formatDate('dd.mm.yy', new Date()));

			if (myClone.attr('data-relatedto') != '' && $('div.done[data-uid="' + myClone.attr('data-relatedto') + '"]').length > 0) {
				$('div.done[data-uid="' + myClone.attr('data-relatedto') + '"]').append(myClone);

			} else {
				$('#donetodo').after(myClone);
			}

			// $('.task [data-val="counterdone"]').toggleClass('arrowDown');
			if ($('.task.done').is(':visible')) {
				//		$('span[data-val="counterdone"]:before').css('content','\25BC');

				myClone.show('fast');
			} else {
				myClone.hide('fast');
			}

			//myClone.find('input[type="checkbox"]').attr('checked','checked');
		} else {
			myClone.removeClass('done');
			myClone.addClass('opentask');
			myClone.find('#chk_' + taskdata.id).removeAttr('checked');
			myClone.find('.completeDate').text('');

			myClone.show('fast');

			if (myClone.attr('data-relatedto') != '' && $('div.opentask[data-uid="' + myClone.attr('data-relatedto') + '"]').length > 0) {
				$('div.opentask[data-uid="' + myClone.attr('data-relatedto') + '"]').append(myClone);

			} else {
				$('.task[id="newtodo"]').before(myClone);
			}

		}
		$task.remove();
	},
	completedHandler : function(event) {
		$Task = $(this).closest('.task');
		TaskId = $Task.attr('data-id');
		checked = $(this).is(':checked');
		
		 $.ajax({
			type : 'POST',
			url : OC.generateUrl('apps/aufgaben/setcompleted'),
			data :{
				id : TaskId,
				checked : checked ? 1 : 0
			},
			success : function(jsondata) {
				task = jsondata;
				OC.Aufgaben.rebuildLeftTaskView();
				//$Task.data('task', task)
				$(task).each(function(i, el) {
					OC.Aufgaben.renderComplete(el);
				});

				$('.task [data-val="counterdone"]').text($('.task.done').length);
			}
		});
		
		
		//return false;
	},
	deleteHandler : function(TASKID) {
		// $Task=$(this).closest('.task');
		//TaskId=$Task.attr('data-id');
		$("#dialogSmall").html(t('aufgaben', 'Are you sure') + '?');

		$("#dialogSmall").dialog({
			resizable : false,
			title : t('aufgaben', 'Delete Task'),
			width : 200,
			height : 160,
			position : {
				my : "center center",
				at : "center center",
				of : window
			},
			modal : true,
			buttons : [{
				text : t('aufgaben', 'No'),
				click : function() {
					$(this).dialog("close");
				}
			}, {
				text : t('aufgaben', 'Yes'),
				click : function() {
					var oDialog = $(this);
					
				$.ajax({
					type : 'POST',
					url : OC.generateUrl('apps/aufgaben/deletetask'),
					data :{
						'id' : TASKID
					},
					success : function(jsondata) {
						oDialog.dialog("close");
						$("#dialogmore").dialog("close");
						if ($('.task[data-id="' + TASKID + '"]').hasClass('done')) {
							tempCount = parseInt($('.task [data-val="counterdone"]').text());
							tempCount -= 1;
							$('.task [data-val="counterdone"]').text(tempCount);
						}
						$('.task[data-id="' + TASKID + '"]').remove();
						OC.Aufgaben.rebuildLeftTaskView();
					}
				});
				
				}
			}],
		});

		return false;
	},
	openShareDialog : function(TaskId) {

		var selCal = $('<select name="calendar" id="calendarAdd"></select>');
		$.each(OC.Aufgaben.mycalendars, function(i, elem) {
			var option = $('<option value="' + elem['id'] + '">' + elem['name'] + '</option>');
			selCal.append(option);
		});

		$('<p>' + t('calendar', 'Please choose a calendar') + '</p>').appendTo("#dialogmore");
		selCal.appendTo("#dialogmore");

		$("#dialogmore").dialog({
			resizable : false,
			title : t('aufgaben', 'Add Task'),
			width : 350,
			height : 200,
			modal : true,
			buttons : [{
				text : t('core', 'Add'),
				click : function() {
					var oDialog = $(this);
					var CalId = $('#calendarAdd option:selected').val();

					$.ajax({
						type : 'POST',
						url : OC.generateUrl('apps/aufgaben/addsharedtask'),
						data :{
							'taskid' : TaskId,
							'calid' : CalId
						},
						success : function(jsondata) {
								OC.Aufgaben.updateList(0);
								OC.Aufgaben.rebuildLeftTaskView();
								$("#dialogmore").html('');
								oDialog.dialog("close");
						}
					});
					
					
				}
			}, {
				text : t('calendar', 'Cancel'),
				click : function() {
					$(this).dialog("close");
					$("#dialogmore").html('');
				}
			}],

		});

		return false;
	},
	newCompleteCalc : function() {
		$Task = $(this).closest('.task');
		TaskId = $Task.attr('data-id');
		$.ajax({
			type : 'POST',
			url : OC.generateUrl('apps/aufgaben/setcompletedpercentmaintask'),
			data : {
				id : TaskId
			},
			success : function(jsondata) {
				var gesamtCptl = jsondata.percentCptl;

				$('div[data-id="' + jsondata.id + '"]  .completeActual.maintask').attr({
					'data-width' : gesamtCptl,
					'title' : gesamtCptl + '% Completed',
					'class' : 'maintask completeActual bgcolor-' + gesamtCptl,
				}).css('width', gesamtCptl + '%');
			}
		});

	},
	newTask : function() {

		$Task = $(this).closest('.task');
		TaskUid = $Task.attr('data-uid');
		if (TaskUid == undefined) {
			TaskUid = '';
		}
		//position = $(this).position();

		$.ajax({
			type : 'POST',
			url : OC.generateUrl('apps/aufgaben/newtask'),
			data : {
				mytaskmode : $('#donetodo').data('mode'),
				mytaskcal : $('#donetodo').data('cal'),
				relatedto : TaskUid
			},
			success : function(data) {
				$("#dialogmore").html(data);
				//$("#tasks_list_details").show('fast');
				$("#dialogmore").dialog({
					resizable : false,
					title : t('aufgaben', 'Add Task'),
					width : 370,

					position : {
						my : 'center center',
						at : 'center center',
						of : $('#app-content')
					},
					modal : false,
				});

				if ($('#donetodo').data('mode') == 'dayselect') {
					$('#startdate').val($('#taskmanagertitle').attr('data-date'));
				}

				$('#newTodo-submit').on('click', function() {
					if ($('#tasksummary').val() != '') {
						OC.Aufgaben.SubmitForm('newitTask', '#taskForm', '#tasks_list_details');
					} else {
						OC.Aufgaben.showMeldung(t('aufgaben', 'Title is missing'));
					}
				});

				$('#tasksummary').bind('keydown', function(event) {
					if (event.which == 13) {
						if ($('#tasksummary').val() != '') {
							OC.Aufgaben.SubmitForm('newitTask', '#taskForm', '#tasks_list_details');
						} else {
							OC.Aufgaben.showMeldung(t('aufgaben', 'Title is missing'));
						}
					}
				});

				$('#newTodo-cancel').on('click', function() {
					$("#dialogmore").dialog("close");
				});

				OC.Aufgaben.getReminderSelectLists();

				OC.Aufgaben.initActionHandler();

				//$('#taskcategories').multiple_autocomplete({source: categoriesSel});

			}
		});
		return false;
	},
	SubmitForm : function(VALUE, FormId, UPDATEAREA) {

		var string = '';
		var objTags = $('#tagmanager').tagit('tags');
		$(objTags).each(function(i, el) {
			if (string == '') {
				string = el.value;
			} else {
				string += ',' + el.value;
			}
		});
		$('#taskcategories').val(string);

		actionFile = 'newtask';
		if (VALUE == 'newitTask') {
			actionFile = 'newtask';
		}
		if (VALUE == 'edititTask') {
			actionFile = 'edittask';
		}

		$(FormId + ' input[name=hiddenfield]').attr('value', VALUE);
        
        $url=OC.generateUrl('apps/aufgaben/'+actionFile);
        $.ajax({
			type : 'POST',
			url : $url,
			data :$(FormId).serialize(),
			success : function(jsondata) {
				OC.Aufgaben.rebuildLeftTaskView();

				if (VALUE == 'newitTask') {
					OC.Aufgaben.showMeldung(t('aufgaben', 'Task creating success!'));
					$('#dialogmore').dialog('close');
					OC.Aufgaben.taskRendering(jsondata);
				}
				if (VALUE == 'edititTask') {
					OC.Aufgaben.showMeldung(t('aufgaben', 'Update success!'));
					if ($('#mytaskmode').val() == 'calendar') {
						OC.Aufgaben.updateList($('#mytaskcal').val());
					}

					if ($('#mytaskmode').val() != 'calendar') {
						OC.Aufgaben.updateListByPeriod($('#mytaskmode').val());
					}
				}
			}
			
		});

	},
	showMeldung : function(TXT) {

		var leftMove = ($(window).width() / 2) - 150;
		var myMeldungDiv = $('<div id="iMeldung" style="left:' + leftMove + 'px"></div>');
		$('#content').append(myMeldungDiv);
		$('#iMeldung').html(TXT);

		$('#iMeldung').animate({
			top : 200
		}).delay(3000).animate({
			top : '-300'
		}, function() {
			$('#iMeldung').remove();
		});

	},
	filter : function(tagText) {
		//$Task=$(this).closest('.task');
		//TaskId=$Task.attr('data-id');
		var saveArray = [];

		$('#tasks_list .categories').find('a').each(function(i, el) {

			if ($(el).attr('title') != '' && $(el).attr('title') == tagText) {
				$Task = $(this).closest('.task');
				TaskId = $Task.attr('data-id');

				saveArray[TaskId] = 1;
			}
		});
		if (saveArray.length > 0) {

			$('#tasks_list .task').each(function(i, el) {
				if (saveArray[$(el).attr('data-id')] != undefined && saveArray[$(el).attr('data-id')]) {
					$(el).addClass('filterActive').show('fast');
				} else {
					if ($(el).attr('id') != 'donetodo')
						$(el).hide('fast');
				}
			});
		}

	},
	updateList : function(CID) {
		$('#loading').show();
		$('#tasks_list').html('');
		$.post(OC.generateUrl('apps/aufgaben/gettasks'), {
			calid : CID
		}, function(jsondata) {
			$('#loading').hide();
			
			var doneTask = $('<div class="task" id="donetodo" data-mode="calendar" data-cal="' + CID + '"><span class="iCount" data-val="counterdone">0</span> <label>'+t('aufgaben','done')+'</label></div>').appendTo($('#tasks_list'));

			OC.Aufgaben.generateTaskList(jsondata);

			if ($('.calListen[data-id="' + CID + '"]').data('permissions') & OC.PERMISSION_CREATE) {
				$('<div class="task" id="newtodo" style="line-height:50px;height:50px;"><span class="button" style="margin-left:34px;margin-top:-2px; margin-bottom:4px;">'+t('aufgaben','New Todo ...')+'</span></div>').appendTo($('#tasks_list'));
				$('#newtodo').on('click', OC.Aufgaben.newTask);
			} else {
				$('<div class="task" id="newtodo" style="display:none;"></div>').appendTo($('#tasks_list'));
			}
			
			/*
			 $('.task .location').each(function(i,el){
			 $(el).tooltip({
			 items: "[data-geo], [title]",

			 content: function() {
			 var element = $( this );
			 if ( element.is( "[data-geo]" ) ) {
			 var text = element.text();
			 return "<img class='map' alt='" + text +
			 "' src='http://maps.google.com/maps/api/staticmap?" +
			 "zoom=14&size=350x350&maptype=terrain&sensor=false&center=" +
			 text + "'>";
			 }
			 if ( element.is( "[title]" ) ) {
			 return element.attr( "title" );
			 }
			 if ( element.is( "img" ) ) {
			 return element.attr( "alt" );
			 }
			 }
			 });
			 });
			 */

		});

	},
	updateListByPeriod : function(MODE) {
		var daySelect = '';
		if (MODE == 'dayselect') {
			daySelect = $('#taskmanagertitle').attr('data-date');

		}
		$('#loading').show();
		$('#tasks_list').html('');
		$.post(OC.generateUrl('apps/aufgaben/gettasks'), {
			mode : MODE,
			sday : daySelect
		}, function(jsondata) {
			$('#loading').hide();
			
	
			$('<div class="task" id="donetodo" data-mode="' + MODE + '" data-cal="0"><span class="iCount" data-val="counterdone">0</span> <label>'+t('aufgaben','done')+'</label></div>').appendTo($('#tasks_list'));

			OC.Aufgaben.generateTaskList(jsondata);

			if (MODE == 'dayselect') {
				$('<div class="task" id="newtodo" style="line-height:50px;height:50px;"><span class="button" style="margin-left:34px;margin-top:-2px; margin-bottom:4px;">'+t('aufgaben','New Todo ...')+'</span></div>').appendTo($('#tasks_list'));
				$('#newtodo').on('click', OC.Aufgaben.newTask);
			} else {
				$('<div class="task" id="newtodo" style="display:none;"></div>').appendTo($('#tasks_list'));
			}
			if (MODE == 'alltasksdone') {
				$('.task [data-val="counterdone"]').addClass('arrowDown');
				$('.task.done').show('fast');
			}
		});
		

	},
	reminder : function(task) {
		if (task == 'init') {
			$('#remCancel').on('click', function() {
				$('#showOwnReminderDev').hide();
				if ($('#new-event').length != 0 || $('#sReminderRequest').val() == '') {
					OC.Aufgaben.reminder('reminderreset');

				} else {
					//if($('#sReminderRequest').val()!=''){}
				}
				return false;
			});
			$('#remOk').on('click', function() {
				OC.Aufgaben.getReminderonSubmit();
				$('#showOwnReminderDev').hide();
				return false;
			});

			$('#showOwnReminderDev').hide();

			//$('.advancedReminder').css('display', 'none');

			OC.Aufgaben.reminder('reminder');
			OC.Aufgaben.reminder('remindertime');
		}
		if (task == 'reminderreset') {
			var sReminderSel = '#sReminderSelect.combobox';
			$(sReminderSel + ' li .colCal').removeClass('isSelectedCheckbox');
			$(sReminderSel + ' li').removeClass('isSelected');
			$('#reminder').val('none');
			$('#reminderoutput').hide();
			$("#reminderoutput").text('');
			$("#sReminderRequest").val('');
			$(sReminderSel + ' li[data-id=none]').addClass('isSelected');
			$(sReminderSel + ' li[data-id=none]').parents(sReminderSel).find('.selector').html($(sReminderSel + ' li[data-id=none]').text());
			$(sReminderSel + ' li[data-id=none]').find('.colCal').addClass('isSelectedCheckbox');
		}

		if (task == 'reminder') {
			$('.advancedReminder').css('display', 'none');

			if ($('#reminderAdvanced option:selected').val() == 'DISPLAY') {

				$('#reminderemailinputTable').css('display', 'none');
				$('#reminderTable').css('display', 'block');
				$('#remindertimeinput').css('display', 'block');
			}
			if ($('#reminderAdvanced option:selected').val() == 'EMAIL') {
				$('#reminderemailinputTable').css('display', 'block');
				$('#reminderTable').css('display', 'block');
				$('#remindertimeinput').css('display', 'block');
			}
		}
		if (task == 'remindertime') {

			$('#reminderemailinputTable').css('display', 'none');
			$('#reminderdateTable').css('display', 'none');
			$('#remindertimeinput').css('display', 'block');
			if ($('#remindertimeselect option:selected').val() == 'ondate') {
				$('#reminderdateTable').css('display', 'block');
				$('#remindertimeinput').css('display', 'none');
			}
		}
	},
	rebuildLeftTaskView : function() {

		$.post(OC.generateUrl('apps/aufgaben/buildleftnavigation'), function(data) {
			$('#tasks_lists').html(data);
			if ($('#donetodo').data('mode') == 'calendar') {
				$('.calListen[data-id="' + $('#donetodo').data('cal') + '"]').addClass('active');
			}
			if ($('#donetodo').data('mode') != 'calendar') {
				$('.taskstimerow[data-id="' + $('#donetodo').data('mode') + '"]').addClass('active');
			}

			$('.calListen').each(function(i, el) {

				$(el).on('click', function() {
					$('.taskstimerow').removeClass('active');
					$('.calListen').removeClass('active');
					$(el).addClass('active');
					$('#taskmanagertitle').text($(el).attr('title'));
					OC.Aufgaben.updateList($(el).attr('data-id'));

				});
			});

			$('.toolTip').tipsy({
				html : true,
				gravity : $.fn.tipsy.autoNS
			});

			$('.taskstimerow').each(function(i, el) {

				$(el).on('click', function() {
					$('.taskstimerow').removeClass('active');
					$('.calListen').removeClass('active');
					$(el).addClass('active');
					$('#taskmanagertitle').text($(el).attr('title'));
					OC.Aufgaben.updateListByPeriod($(el).attr('data-id'));

				});
			});
			OC.Aufgaben.buildCategoryList();
			
			$('div[data-id="lTimelineHolder"]').hide();
			$('#lTimeline').click(function() {
	
				if (! $('div[data-id="lTimelineHolder"]').is(':visible')) {
					$('h3 #lTimeline i.ioc-chevron-down').removeClass('ioc-rotate-270');
					$('div[data-id="lTimelineHolder"]').show('fast');
				} else {
					$('div[data-id="lTimelineHolder"]').hide('fast');
					$('h3 #lTimeline i.ioc-chevron-down').addClass('ioc-rotate-270');
				}
			});
				
			$('#categoryTasksList').hide();
			$('#showCategory').click(function() {
	
				if (! $('#categoryTasksList').is(':visible')) {
					$('h3 #showCategory i.ioc-chevron-down').removeClass('ioc-rotate-270');
					$('#categoryTasksList').show('fast');
				} else {
					$('#categoryTasksList').hide('fast');
					$('h3 #showCategory i.ioc-chevron-down').addClass('ioc-rotate-270');
				}
			});
			
			
			$('#lCalendar').click(function() {
				if (! $('#datepickerNav').is(':visible')) {
					$('h3 #lCalendar i.ioc-chevron-down').removeClass('ioc-rotate-270');
					$('#datepickerNav').show('fast');
				} else {
					$('#datepickerNav').hide('fast');
					$('h3 #lCalendar i.ioc-chevron-down').addClass('ioc-rotate-270');
				}
			});

			
			$("#datepickerNav").datepicker({
				minDate : null,
				onSelect : function(value, inst) {
					var date = inst.input.datepicker('getDate');
					$('#taskmanagertitle').attr('data-date', $.datepicker.formatDate('dd.mm.yy', date));
					$('#taskmanagertitle').text(t('aufgaben', 'Tasks') + ' '+t('aufgaben', 'on')+' ' + $.datepicker.formatDate('DD, dd.mm.yy', date));
					OC.Aufgaben.updateListByPeriod('dayselect');
				}
			});

			if (OC.Aufgaben.firstLoading == true) {
				$('#tasks_list').height($(window).height() - 78);
				$('#tasks_list').width($(window).width() - 260);
				$('#tasksListOuter').width($(window).width() - 262);
				$('#tasks_list_details').height($(window).height() - 90);

				$('.taskstimerow[data-id="' + OC.Aufgaben.startMode + '"]').addClass('active');
				$('#taskmanagertitle').text($('.taskstimerow[data-id="' + OC.Aufgaben.startMode + '"]').attr('title'));

			}

		});

	},
	checkShowEventHash : function() {
		var id = parseInt(window.location.hash.substr(1));
		if (id) {
			var calEvent = {};
			calEvent['id'] = id;

			OC.Aufgaben.showEditTask(calEvent);

		}
	},
	categoriesChanged : function(newcategories) {
		categoriesSel = $.map(newcategories, function(v) {
			return v;
		});
		var newCat = {};
		$.each(newcategories, function(i, el) {
			newCat[el.name] = el.color;
		});
		OC.Aufgaben.categories = newCat;

		$('#taskcategories').multiple_autocomplete('option', 'source', categoriesSel);
		OC.Aufgaben.buildCategoryList();
	},
	buildCategoryList : function() {
		var htmlCat = '';
		$.each(OC.Aufgaben.tags, function(i, elem) {
			htmlCat += '<li class="categorieslisting" title="' + elem['name'] + '"><span class="catColPrev" style="background-color:' + elem['bgcolor'] + ';color:' + elem['color'] + '">' + elem['name'].substring(0, 1) + '</span> ' + elem['name'] + '</li>';
		});

		$('#categoryTasksList').html(htmlCat);

		$('.categorieslisting').each(function(i, el) {
			$(el).on('click', function() {
				if ($(this).hasClass('isFilter')) {
					$(this).removeClass('isFilter');
					$('.task .categories a').each(function(i, el) {
						$Task = $(this).closest('.task');
						$Task.removeClass('filterActive');
						$('.task').each(function(i, el) {
							$(el).show('fast');
						});
					});
				} else {
					$('.task [data-val="counterdone"]').addClass('arrowDown');
					OC.Aufgaben.filter($(this).attr('title'));
					$(this).addClass('isFilter');
				}
			});
		});
		$(".categorieslisting").draggable({
			appendTo : "body",
			helper : "clone",
			cursor : "move",
			delay : 500,
			start : function(event, ui) {
				ui.helper.addClass('draggingCategory');
			}
		});

	},
	calcDimension : function() {
		var winWidth = $(window).width();
		var winHeight = $(window).height();

		if (winWidth > 768) {

			$('#tasks_list').height(winHeight - 95).width(winWidth - 251);
			$('#tasks_lists').height(winHeight - 45);
			$('#tasksListOuter').width(winWidth - 253);
			$('#tasks_list_details').height(winHeight - 90);
		} else {
			$('#tasksListOuter').width(winWidth - 8);
			$('#tasks_list').width(winWidth - 7).height(winHeight - 78);
			$('#tasks_lists').height(winHeight - 45);
			$('#tasks_list_details').height(winHeight - 90);
		}
	},
	getInit : function() {
		$.getJSON(OC.generateUrl('apps/aufgaben/getdefaultvaluestasks'), {},
		 function(jsondata) {
			if (jsondata) {
				OC.Aufgaben.categories = jsondata.categories;
				OC.Aufgaben.mycalendars = jsondata.mycalendars;
				OC.Aufgaben.tags = jsondata.tags;
				
				OC.Aufgaben.rebuildLeftTaskView();
				OC.Aufgaben.updateListByPeriod(OC.Aufgaben.startMode);
			}
		}
		);
	}
};

$(window).bind('hashchange', function() {
	OC.Aufgaben.checkShowEventHash();
});

var resizeTimeout = null;
$(window).resize(function() {
	if (resizeTimeout)
		clearTimeout(resizeTimeout);
	resizeTimeout = setTimeout(function() {
		OC.Aufgaben.calcDimension();
		if ($("#dialogmore").is(':visible')) {
			$('#dialogmore').dialog('option', "position", {
				my : 'center center',
				at : 'center center',
				of : $('#app-content')
			});
		}
	}, 500);
});

$(document).ready(function() {
	
	OC.Aufgaben.getInit();
	$(document).on('click', '#edit-event a.share', function(event) {
		event.stopPropagation();

		$('#edit-event #dropdown').css({
			'top' : $(event.target).offset().top + 40,
			'left' :$('#edit-event').offset().left
		});
	});
	
	
}); 