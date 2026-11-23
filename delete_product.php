<?php
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
  page_require_level(2);
  require_post('product.php');
?>
<?php
  $product = find_by_id('products',(int)$_POST['id']);
  if(!$product){
    $session->msg("d","Missing Product id.");
    redirect('product.php');
  }
  // La base borra en cascada las ventas de un producto eliminado (H-13):
  // se perdería el historial. Igual que la API nueva, se impide.
  $ventas = $conexion->uno('SELECT COUNT(*) AS n FROM sales WHERE product_id = ?', [(int)$product['id']]);
  if((int)$ventas['n'] > 0){
    $session->msg("d","No se puede eliminar un producto con ventas registradas: se perdería el historial.");
    redirect('product.php');
  }
?>
<?php
  $delete_id = delete_by_id('products',(int)$product['id']);
  if($delete_id){
      $session->msg("s","Products deleted.");
      redirect('product.php');
  } else {
      $session->msg("d","Products deletion failed.");
      redirect('product.php');
  }
?>
