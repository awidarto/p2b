@layout('public')

@section('content')
<div class="row">
    {{-- print_r($cart['items'])}}
  <div class="span12">
    <h3>Check Out Success</h3>
    <p>
        Thank you for shopping at Peach To Black, feel free to go back into our catalog again and shop for more.
    </p>
    <p>
        Your payment confirmation code is : <span class="big-code">{{ $cart['confirmationCode']}}</span>
    </p>
    <p>
        If you've made your transfer payment, kindly confirm your payment {{ HTML::link('shop/confirm', 'here') }}, using above code, so we can proceed with the delivery. Have a nice day ! 
    </p>

    <table class='dataTable' id="shoppingcart">
      <thead>
        <tr class="headshoppingcart">
          <th class="span2"></th>
          <th class="span4">ITEM DESCRIPTION</th>
          <th class="span1">SIZE</th>
          <th class="span1">COLOR</th>
          <th class="span1">QTY</th>
          <th class="span2">UNIT PRICE</th>
          <th class="span2">PRICE TOTAL</th>
        </tr>
      </thead>
      <tbody>

      <?php

      $totalPrice = 0;

      //print_r($cart);

      ?>
      @foreach($cart['items'] as $key=>$val)

          <?php
            $i = $products[$key]['defaultpic'];
            $product_prefix = $key;
          ?>

          @foreach($val as $k=>$v)
            <?php
              $kx = str_replace('#', '', $k);
            ?>
          <tr>
            <td class="span2 image">
                @if(file_exists(realpath('public/storage/products/'.$key).'/sm_pic0'.$i.'.jpg'))
                    {{ HTML::image(URL::base().'/storage/products/'.$key.'/sm_pic0'.$i.'.jpg?'.time(), 'sm_pic0'.$i.'.jpg', array('id' => $key)) }}
                @endif
            </td>
            <td class="span4"><h5>{{ $products[$key]['name'];}}</h5>{{ $products[$key]['description'];}}</td>

            <?php $qty = 0;?>
              <?php

                  $vx = explode('_',$k);
                  $size = $vx[0];
                  $color = $vx[1];

              ?>
            <td class="span1 central" >
              {{ $size }}
            </td>
            <td class="span1 central" >
              <span class="color-chip" style="background-color: {{ $color }}; ">&nbsp;</span>
            </td>
            <td class="span2 central">
              {{  $cart['items'][$product_prefix][$k]['actual'] }}
              {{ Form::hidden($product_prefix.'_'.$k.'_qty',$cart['items'][$product_prefix][$k]['actual'],array('class'=>'qty-box')) }}<br />
            </td>

            <td class="span2 price">
              {{ $cart['prices'][$product_prefix][$k]['unit_price_fmt'] ;}}
              <input type="hidden" name="{{$key}}_retailPrice" value="{{$cart['prices'][$product_prefix][$k]['unit_price']}}" />
            </td>
            <td class="span2 price">
              {{ $cart['prices'][$product_prefix][$k]['sub_total_price_fmt'] ;}}
          </tr>

          @endforeach

          <?php
            $totalPrice += $qty * (double) $products[$key]['retailPrice'];
          ?>
      @endforeach

          <tr>
            <td colspan="5"></td>
            <td class="span2 price">
              <h4 class="titleselectbox">total</h4>
            </td>
            <td class="span2 price">
              {{ $cart['prices']['total_due_fmt'] }}
            </td>
          </tr>

      </tbody>
    </table>
      
    </div>

</div>

<style type="text/css">
.currency-display{
  text-align: right;
}

table.dataTable thead tr{
    border-bottom: 1px solid #ccc;
}

table.dataTable td{
    border:none;
}

</style>

</script>


@endsection