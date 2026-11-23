<?php
  $page_title = 'Add Sale';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
   page_require_level(3);
?>
<?php

  if(isset($_POST['add_sale'])){
    $req_fields = array('s_id','quantity' );
    validate_fields($req_fields);
    if(empty($errors)){
      // Las reglas de la venta viven en src/Ventas/RegistroDeVentas.php:
      // el total se calcula en el servidor, se valida el stock y todo
      // ocurre en una transacción (corrige H-04, H-05 y H-08).
      $registro = new App\Ventas\RegistroDeVentas(
        new App\Ventas\ProductoRepositorioMysql($conexion),
        new App\Ventas\VentaRepositorioMysql($conexion),
        $conexion
      );
      try {
        $venta = $registro->registrar((int)$_POST['s_id'], (int)$_POST['quantity'], new DateTimeImmutable());
        $session->msg('s', "Sale added. ");
      } catch (DomainException $e) {
        $session->msg('d', $e->getMessage());
      }
      redirect('add_sale.php', false);
    } else {
      $session->msg("d", $errors);
      redirect('add_sale.php',false);
    }
  }

?>
<?php include_once('layouts/header.php'); ?>
<div class="row">
  <div class="col-md-6">
    <?php echo display_msg($msg); ?>
    <form method="post" action="ajax.php" autocomplete="off" id="sug-form">
            <?php echo csrf_field(); ?>
        <div class="form-group">
          <div class="input-group">
            <span class="input-group-btn">
              <button type="submit" class="btn btn-primary">Find It</button>
            </span>
            <input type="text" id="sug_input" class="form-control" name="title"  placeholder="Search for product name">
         </div>
         <div id="result" class="list-group"></div>
        </div>
    </form>
  </div>
</div>
<div class="row">

  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading clearfix">
        <strong>
          <span class="glyphicon glyphicon-th"></span>
          <span>Sale Eidt</span>
       </strong>
      </div>
      <div class="panel-body">
        <form method="post" action="add_sale.php">
            <?php echo csrf_field(); ?>
         <table class="table table-bordered">
           <thead>
            <th> Item </th>
            <th> Price </th>
            <th> Qty </th>
            <th> Total </th>
            <th> Date</th>
            <th> Action</th>
           </thead>
             <tbody  id="product_info"> </tbody>
         </table>
       </form>
      </div>
    </div>
  </div>

</div>

<?php include_once('layouts/footer.php'); ?>
