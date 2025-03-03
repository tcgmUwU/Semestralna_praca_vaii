<?php

namespace App\Controllers;

use App\Core\AControllerBase;
use App\Core\DB\Connection;
use App\Core\HTTPException;
use App\Core\Model;
use App\Core\Responses\RedirectResponse;
use App\Core\Responses\Response;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\User;
use http\Exception;
use PDO;

class ProductController extends AControllerBase
{
    /**
     * Authorize controller actions
     * @param $action
     * @return bool
     */
    public function authorize($action)
    {
        switch ($action) {
            case 'getJson':
            case 'getSizesJson':
            case 'display':
                return true;
            default:
                //return $this->app->getAuth()->isLogged();
                if ($this->app->getAuth()->isLogged()) {
                    $queryResult = User::getAll('`email` = ?', [$this->app->getAuth()->getLoggedUserId()]);
                    $user = $queryResult[0];
                    return (strcmp($user->getRole(), 'admin') === 0);
                }
                return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function index(): Response
    {
        //return $this->html();
        return $this->html();
    }

    public function getJson(): Response
    {
        return $this->json(Product::getAll());
    }

    public function getSizesJson(): Response
    {
        $id = (int)$this->app->getRequest()->getValue('id');
        return $this->json(ProductSize::getAll('`productId` = ?', [$id], orderBy: '`id` asc'));
    }

    public function add(): Response
    {
        return $this->html();
    }

    public function edit(): Response
    {
        $id = (int)$this->request()->getValue('id');
        $product = Product::getOne($id);

        if (is_null($product)) {
            throw new HTTPException(404);
        }

        return $this->html(
            [
                'product' => $product
            ]
        );
    }

    public function save()
    {
        $id = (int)$this->request()->getValue('id');

        $price = (double)$this->request()->getValue('price');
        $name = strip_tags($this->request()->getValue('productName'));
        $colour = strip_tags($this->request()->getValue('colour'));

        if ($price == 0 || $name == '' || $colour == '') {
            return new RedirectResponse($this->url("admin.index", ['result' => 'Required field/s not filled']));
        }
        
        $sizes = array("XS", "S", "M", "L", "XL");
        if ($id > 0) {
            $product = Product::getOne($id);
            $productSizes = ProductSize::getAll('`productId` = ?', [$id], orderBy: '`id` asc');
            $index = 0;
            foreach ($productSizes as $psize) {
                $qty = (int)$this->request()->getValue($sizes[$index]);
                $pf = (double)$this->request()->getValue($sizes[$index] . '_PF');
                if ($pf == 0) {
                    return new RedirectResponse($this->url("admin.index", ['result' => 'Required field/s not filled']));
                }
                $psize->setQuantity($qty);
                $psize->setPriceFactor($pf);
                $index++;
            }
        } else {
            $product = new Product();
            $productSizes = array();
            foreach ($sizes as $size) {
                $productSize = new ProductSize();
                $productSize->setPriceFactor(1);
                $productSize->setQuantity(0);
                $productSize->setSize($size);
                $productSizes[] = $productSize;
            }
        }

        $product->setPrice($price);
        $product->setName($name);
        $product->setColour($colour);

        $filename = $this->request()->getFiles()['image']['name'];
        if ($id > 0) {
            if (strlen($filename) > 0) {
                if (!move_uploaded_file($this->request()->getFiles()['image']['tmp_name'], './public/images/' . $filename)) {
                    return $this->redirect($this->url('admin.index', ['result' => 'Image upload failed']));
                }
                $product->setThumbnail($filename);
            }
        } else {
            if (!move_uploaded_file($this->request()->getFiles()['image']['tmp_name'], './public/images/' . $filename)) {
                return $this->redirect($this->url('admin.index', ['result' => 'Image upload failed']));
            }
            $product->setThumbnail($filename);
        }
        $product->save();
        $id = $product->getId();

        foreach ($productSizes as $psize) {
            $psize->setProductId($id);
            $psize->save();
        }

        return new RedirectResponse($this->url("admin.index", ['result' => 'Product added successfully']));
    }

    public function delete()
    {
        $id = (int)$this->request()->getValue('id');
        $product = Product::getOne($id);

        if (is_null($product)) {
            throw new HTTPException(404);
        } else {
            foreach (ProductSize::getAll('`productId` = ?', [$id]) as $psize) {
                $psize->delete();
            }
            $product->delete();
            return new RedirectResponse($this->url("admin.index"));
        }
    }

    public function display(): Response
    {
        $id = (int)$this->request()->getValue('id');
        $product = Product::getOne($id);

        if (is_null($product)) {
            throw new HTTPException(404);
        }

        return $this->html(
            [
                'product' => $product
            ]
        );
    }
}