<!DOCTYPE html>
  <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <title>Виртуальный деканат</title>
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
        <link type="text/css" rel="stylesheet" href="/vd_sources/css/materialize.css" media="screen,projection"/>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    </head>

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

	    <header class="header">															<!--			Белая панелька сверху						-->
	        <nav>																		<!--														-->
	    	  	<div class="nav-wrapper white">											<!--														-->
	      			<a class="brand-logo center">										<!--														-->
	      				<font color=#7F1E2F>											<!--														-->
	      					Единый деканат												<!--														-->
	      				</font>															<!--														-->
	      			</a>																<!--														-->
	      			<a href="https://stankin.ru/" style="margin-right: 1%" class="right"><!--														-->
	    				<img src="/vd_sources/img/stankin_logo.png">								<!--														-->
	    			</a>																<!--														-->
	    		</div>																	<!--														-->
	  		</nav>																		<!--														-->
	    </header>																		<!--														-->

	    <div class="row center">														<!--			Белая карточка для входа					-->
	      	<div class="card-panel white">												<!--														-->
	        		<font color=#7F1E2F size="5"><b>ВХОД</b></font>						<!--														-->
	        	<div class="input-field" style="margin-left: 0">						<!--														-->
		    		<input type="text" id="reg-login" class="validate"><!-----------------------------Тут ввод логина, id элемента reg-login		-->
		    		<label>																<!--														-->
	            		Логин															<!--														-->
	          		</label>															<!--														-->
	          	</div>																	<!--														-->
	          	<div class="input-field" style="margin-left: 0">						<!--														-->
	          		<input type="password" id="reg-password" class="validate"><!--------------------------Тут ввод пароля, id элемента reg-password		-->
		    		<label>																<!--														-->
	            		Пароль															<!--														-->
	          		</label>															<!--														-->
			  	</div>																	<!--														-->
			  	<div class="row center">												<!--														-->
	  				<a class="waves-effect waves-light grey darken-2 btn" id="reg-button"><!----------Тут кнопка для входа, id элемента reg-button	-->
	  					Войти															<!--														-->
	  				</a>																<!--														-->
	  			</div>																	<!--														-->
	      	</div>																		<!--														-->
	  	</div>																			<!--														-->

    	<script type="text/javascript" src="/vd_sources/js/materialize.js"></script>
     	<script type="text/javascript" src="/vd_sources/js/reg.js"></script><!------------------------------------Тут подключаем reg.js-->
    </body>
</html>