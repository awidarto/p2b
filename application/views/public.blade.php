<!DOCTYPE html>
<html>
  <head>
    <title>{{ Config::get('site.title')}}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">


    {{ HTML::style('bootstrap/css/bootstrap.css') }}

    {{ HTML::style('css/select2.css') }}
    {{ HTML::style('css/flick/jquery-ui-1.9.2.custom.min.css') }}
    {{ HTML::style('css/smart_wizard.css') }}
    {{ HTML::style('content/css/icomoon.css') }}
    {{ HTML::style('css/jquery.simplecolorpicker.css') }}


    {{ HTML::style('css/fontcard.css') }}

    {{ HTML::style('css/shopfront.css') }}

    {{ HTML::style('css/bootstrap-timepicker.css') }}

    {{ HTML::style('css/bootstrap-datetimepicker.min.css') }}

    {{ HTML::script('js/jquery-1.8.3.min.js') }}
    {{ HTML::script('js/jquery.elevateZoom-2.5.5.min.js') }}

    <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="../assets/js/html5shiv.js"></script>
    <![endif]-->

    <!-- Fav and touch icons -->
  <link rel="apple-touch-icon-precomposed" sizes="144x144" href="../assets/ico/apple-touch-icon-144-precomposed.png">
  <link rel="apple-touch-icon-precomposed" sizes="114x114" href="../assets/ico/apple-touch-icon-114-precomposed.png">
  <link rel="apple-touch-icon-precomposed" sizes="72x72" href="../assets/ico/apple-touch-icon-72-precomposed.png">
  <link rel="apple-touch-icon-precomposed" href="../assets/ico/apple-touch-icon-57-precomposed.png">
  <link rel="shortcut icon" href="../assets/ico/favicon.png">



  </head>
  <body>

    <div id="wrap">
      @yield('publictopnav')

      <div class="container">
        <div class="row-fluid">
          <div class="span12">
            @yield('content')
          </div>
        </div>
      </div>


      <div id="push"></div>

    </div>


    <div id="footer">
      <div class="container">
        <p class="social-footer">
          <i class="icon-facebook"></i>
          <i class="icon-twitter"></i>
          <i class="icon-pinterest"></i>
          <i class="icon-google-plus-3"></i>
        </p>
        <p>
          &copy;2013 Peach To Black
        </p>
      </div>
    </div>

<script type="text/javascript">
  base = '{{ URL::base() }}/';
</script>


    {{ HTML::script('js/jquery-ui-1.9.2.custom.min.js') }}
  
    {{ HTML::script('js/jquery.dataTables.min.js') }}

    {{ HTML::script('bootstrap/js/bootstrap.js') }}


    {{ HTML::script('js/bootstrap-timepicker.js') }}   
    {{ HTML::script('js/bootstrap-datetimepicker.min.js') }}   


    {{ HTML::script('js/jquery.simplecolorpicker.js') }}

    {{ HTML::script('js/jquery.tagsinput.min.js') }}

    {{ HTML::script('js/pnu.js') }}

  </body>
</html>