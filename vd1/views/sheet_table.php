<!DOCTYPE html>
<html lang="ru">
    <head>
	    <meta charset="UTF-8">
	    <meta http-equiv="X-UA-Compatible" content="IE=edge">
	    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	    <title>Ведомость по группе: <?php echo get_group_info($group_id)['title']; ?></title>
	    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
	    <link type="text/css" rel="stylesheet" href="/vd_sources/css/materialize.css" media="screen,projection"/>
	    <link rel="stylesheet" href="https://cdn.componentator.com/spa.min@18.css"/>
	    <script src="https://cdn.componentator.com/spa.min@18.js"></script>
	    <script type="text/javascript" src="/vd_sources/js/materialize.js"></script>
	    <script type="text/javascript" src="/vd_sources/js/table.js"></script>
    </head>

    <div data-items='<?php echo json_encode(get_group_students($group_id)); ?>' style="display: none" id="table-data"></div>

    <body>
    	<style>
	        html, body
	        {
	          background-color: #F2F2F2;
	        }

	        .card-panel
	        {
	          width: 450px;
	          margin: 15px auto;
	        }
    	</style>

    	<div style='padding: 20px; border: none !important'>
    		<table class='striped table'>
			    <thead> 
			        <tr> 
			            <th>№</th>
			            <th>Фамилия, инициалы</th>
			            <th>№ зач. книжки</th>
			            <th>Балл</th>
			           	<th style='width:150px'>Оценка</th>
			            <th>Дата</th>
			            <th></th>
			        </tr>
			    </thead>

			    <div class='valign-wrapper' style='padding-bottom: 20px'>
			    	<a href="/sheet" style="width: 20px; height: 20px; margin-right: 15px"><i class="material-icons" style="color: #7F1E2F">arrow_backward</i></a>
			    	<div style='margin-bottom: -5px'>Ведомость по группе: <?php echo get_group_info($group_id)['title']; ?></div>
			    </div>

			    <tbody data-jc="repeater__datasource" >             
			        <script type="text/html">
			            <tr data-index="$index">
			                <td>{{index+1}}</td>
			                <td class='edit' data-type='textbox' data-field='name'>{{name}}</td>
			                <td class='edit' data-type='textbox' data-field='student_id'>{{student_id}}</td>
			                <td class='edit' data-type='textbox' data-field='score'>
                                <div class="input-field">
                                    <input value="{{score}}" id="score" type="text" class="validate">
                                    <label for="last_name">Балл</label>
                                </div>
                            </td>
			                <td class='edit' data-type='textbox' data-field='grade'>
                                <div class="input-field">
                                    <input value="{{grade}}" id="grade" type="text" class="validate">
                                    <label for="grade">Оценка</label>
                                </div>
                            </td>
			                <td class='edit' data-type='textbox' data-field='date'>{{date}}</td>
			                <td><button type='button' class='btn btn-floating' data-bind="rem__click:remRow"  data-id='$index' title='Удалить запись'><i class='material-icons'>delete</i></button></td>
			            </tr>
			        </script>   
			    </tbody>    

			    <tfooter>
			        <tr>
                        <th></th>
			            <th><div data---="textbox__form.name__required:true;type:text;placeholder:Фамилия, инициалы;class:form-control input-sm;"></div></th>
			            <th><div data---="textbox__form.student_id__required:true;placeholder:№ зач. книжки;class:form-control input-sm;"></div></th>
			            <th><div data---="textbox__form.score__required:true;placeholder:Балл;class:form-control input-sm;"></div></th>
			           	<th><div data---="textbox__form.grade__required:true;placeholder:Оценка;class:form-control input-sm;"></div></th>
			           	<th></th>
			            <th><button type='button' class='btn btn-floating' data-bind="rem__click:addRow"  data-id='$index' title='Добавить запись'><i class='material-icons'>add</i></button></th>
			        </tr>
			    </tfooter>    
			</table>              
    	</div>    
