<?php


/**
 * www routes for static pages.
 */

$app->get('/', function ($request, $response, $args) {
  return $this->view->render($response, 'pages/www/landing.html', $args);
});

$app->get('/features/', function ($request, $response, $args) {
  return $this->view->render($response, 'pages/www/features/premium.html', $args);
});

$app->get('/education/', function ($request, $response, $args) {
  return $this->view->render($response, 'pages/www/features/education.html', $args);
});

$app->get('/business/', function ($request, $response, $args) {
  return $this->view->render($response, 'pages/www/features/business.html', $args);
});

$app->get('/pricing/', function ($request, $response, $args) {
  return $this->view->render($response, 'pages/www/pricing/plans.html', $args);
});

$app->get('/education-pricing/', function ($request, $response, $args) {
  return $this->view->render($response, 'pages/www/pricing/education.html', $args);
});

$app->get('/nonprofit-pricing/', function ($request, $response, $args) {
  return $this->view->render($response, 'pages/www/pricing/nonprofit.html', $args);
});

$app->get('/templates/', function ($request, $response, $args) {
  return $this->view->render($response, 'pages/www/templates.html', $args);
});

$app->get('/community/', function ($request, $response, $args) {
  return $this->view->render($response, 'pages/www/community.html', $args);
});

$app->get('/terms/', function ($request, $response, $args) {
  return $this->view->render($response, 'pages/www/terms.html', $args);
});

$app->get('/privacy-policy/', function ($request, $response, $args) {
  return $this->view->render($response, 'pages/www/privacy.html', $args);
});

$app->get('/about/', function ($request, $response, $args) {
  return $this->view->render($response, 'pages/www/about.html', $args);
});

$app->get('/bundle.css', function ($request, $response, $args) {
  $scss_filter = new \Assetic\Filter\ScssphpFilter();
  $scss_filter->setFormatter('Leafo\ScssPhp\Formatter\Crunched');
  $css_bundle = new \Assetic\Asset\AssetCollection(
    new \Assetic\Asset\GlobAsset('assets/css/bundle/main.scss'),
    array($scss_filter)
  );

  $response = $response->withHeader('Content-type', 'text/css');
  $response = $response->withHeader('Cache-Control', 'max-age=315360000');
  $response->write($css_bundle->dump());
  return $response;
});

$app->get('/bundle.js', function ($request, $response, $args) {
  $mins_js_filter = new \Assetic\Filter\JSMinFilter();
  $js_bundle = new \Assetic\Asset\AssetCollection(
    new \Assetic\Asset\GlobAsset('assets/js/bundle/*'),
    array($mins_js_filter)
  );

  $response = $response->withHeader('Content-type', 'application/javascript');
  $response = $response->withHeader('Cache-Control', 'max-age=315360000');
  $response->write($js_bundle->dump());
  return $response;
});

$app->group('/admin/', function () { 
  $this->get('', function ($request, $response, $args) {
      
    
     $db = $this->get('mysql');
     //$statement = $db->prepare('SELECT id, `path`, page_status, last_modified_by FROM landing_pages WHERE `path` IS Not Null;');
     
   //Display  only if Path have some data  - Added By Deepa
     $statement = $db->prepare('SELECT id, `path`, page_status, last_modified_by FROM landing_pages WHERE `path` > "";'); 
     
     $statement->execute();
     
     //Display , only if Path have some data  - Added By Deepa
     if ($statement->rowCount() == 0) {  
         return $response->write("The page you are trying to acess don't have any records.")->withStatus(204);
     }
     //else {  
        $result = $statement->fetchAll();
        $array = array();
     
        $admin_user_tokens = $this->get('admin_tokens');
    
        foreach($result as $valid_pages) {
          $page = array(
              'id'                => $valid_pages['id'],
              'url'               => $valid_pages['path'],
              'status'            => $valid_pages['page_status'],
              'last_modified_by'  => $admin_user_tokens[$valid_pages['last_modified_by']]
            );
            array_push($array, $page);
        }

        $args = array( 'pages' => $array );
        return $this->view->render($response, 'pages/www/admin.html', $args);
     //}
  });

  $this->post('landing_page/{id}/delete/', function ($request, $response, $args) {
 
    $db = $this->get('mysql');
     $id = $args['id'];
    $date = date("Y-m-d H:i:s");
           
    $statement = $db->prepare("UPDATE landing_pages SET page_status = 'deleted', deleted_at = :date WHERE id = :id;");
    $statement->bindParam(':id', $id, PDO::PARAM_INT);
    $statement->bindParam(':date', $date, PDO::PARAM_STR);
    $statement->execute();
    if ($statement->rowCount() === 1) {
        return $response->withHeader('Location', '/admin/')->withStatus(302);
    } 
    else { 
        //Allow user to delete only if the record exists and the status is not deleted - Added By Deepa
        $statement = $db->prepare("SELECT count(id) as count, page_status FROM landing_pages WHERE id = :id;"); 
        $statement->bindParam(':id', $id, PDO::PARAM_INT);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $count  = $result['count'];
            $page_status  = $result['page_status'];
        }          
        if ($count == 0) {
            return $response->write("The page you tried to delete is invalid.")->withStatus(500);
        }
        else if ($page_status=='deleted') {
            return $response->write("The page you tried to delete is already deleted.")->withStatus(500);
        }
        else {    return $response->write("Error Deleting Page.")->withStatus(500); }
    }
    
  });
  

  $this->post('landing_page/{id}/publish/', function ($request, $response, $args) {

    $db = $this->get('mysql');
     $id = $args['id'];
    $date = date("Y-m-d H:i:s");
    
    $statement = $db->prepare("UPDATE landing_pages SET page_status = 'publish', published_at = :date WHERE id = :id;");
    $statement->bindParam(':id', $id, PDO::PARAM_INT);
    $statement->bindParam(':date', $date, PDO::PARAM_STR);
    $statement->execute();

    if ($statement->rowCount() === 1) {
        return $response->withHeader('Location', '/admin/')->withStatus(302);        
    } 
    else {        
        //Allow user to publish  only if the record exists and the status is not publish - Added By Deepa
        $statement = $db->prepare("SELECT count(id) as count, page_status FROM landing_pages WHERE id = :id;"); 
        $statement->bindParam(':id', $id, PDO::PARAM_INT);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $count  = $result['count'];
            $page_status  = $result['page_status'];
        }          
        if ($count == 0) {
            return $response->write("The page you tried to publish is  invalid.")->withStatus(500);
        }
        else if ($page_status=='publish') {
            return $response->write("The page you tried to publish is already published.")->withStatus(500);
        }
        else if ($page_status=='deleted') {
            return $response->write("The page you tried to publish is  deleted.")->withStatus(500);
        }
        else {     return $response->write("Error publishing page.")->withStatus(500); }
    }
  });

  $this->post('landing_page/{id}/unpublish/', function ($request, $response, $args) {

    $db = $this->get('mysql');
    $id = $args['id'];

    $statement = $db->prepare("UPDATE landing_pages SET page_status = 'draft' WHERE id = :id;");
    $statement->bindParam(':id', $id, PDO::PARAM_INT);
    $statement->execute();

    if ($statement->rowCount() === 1) {
        return $response->withHeader('Location', '/admin/')->withStatus(302);
    } else {
         //Allow user to unpublish  only if the record exists and the status is  publish - Added By Deepa
        $statement = $db->prepare("SELECT count(id) as count, page_status FROM landing_pages WHERE id = :id;"); 
        $statement->bindParam(':id', $id, PDO::PARAM_INT);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $count  = $result['count'];
            $page_status  = $result['page_status'];
        }          
        if ($count == 0) {
            return $response->write("The page you tried to unpublish is  invalid.")->withStatus(500);
        }
        else if ($page_status=='publish') {
            return $response->write("The page you tried to unpublish is already unpublished.")->withStatus(500);
        }
        else if ($page_status=='deleted') {
            return $response->write("The page you tried to unpublish is  deleted.")->withStatus(500);
        }
        else { return $response->write("Error unpublishing page.")->withStatus(500); }
    }        
    
  });

  $this->get('landing_page/create/', function ($request, $response, $args) {
      $args['action'] = "/admin/landing_page/create/";
      return $this->view->render($response, 'pages/www/admin/landing_page_editor.html', $args);
  });

  $this->post('landing_page/create/', function ($request, $response, $args) {
    //Get POST data
    $data = $request->getParams();
    $template_content = post_parser($data);
    $admin_cookie = $request->getCookieParams();

    //validate url
    if (!preg_match('/^[A-Za-z0-9_-]*$/', $data['url']) || $data['url'] === "" ) {
        return $response->write("The URL is invalid, could not create the page.")->withStatus(400);
    }
    

    //Update DB
    $db = $this->get('mysql');
    $path = $data['url'];

    $path = $data['url'];
    $template_content_str = json_encode($template_content);
    $admin = $admin_cookie['venngage_admin_pass'];
    $date = date("Y-m-d H:i:s");
    
    
    //Allow user to create  only if the path doesnot exists  - Added By Deepa
        $statement = $db->prepare("SELECT count(id) as count FROM landing_pages WHERE path = :path;"); 
         $statement->bindParam(':path', $path, PDO::PARAM_STR);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $count  = $result['count'];            
        }          
        if ($count == 1) {
            return $response->write("The page you tried to create already exists. Please create with different path.")->withStatus(500);
        }
        
    $statement = $db->prepare("INSERT INTO landing_pages (`path`, template_content, created_at, page_status, last_modified_by) VALUES (:path, :template_content, :date, 'draft', :admin)");

    $statement->bindParam(':path', $path, PDO::PARAM_STR);
    $statement->bindParam(':template_content', $template_content_str, PDO::PARAM_STR);
    $statement->bindParam(':admin', $admin, PDO::PARAM_STR);
    $statement->bindParam(':date', $date, PDO::PARAM_STR);

    $statement->execute();

    if ($statement->rowCount() === 1) {
        return $response->withHeader('Location', '/'.$path.'/')->withStatus(302);
    } else {
        return $response->write("Error creating page")->withStatus(500);
    }
  });

  $this->post('landing_page/{id}/save/', function ($request, $response, $args) {

    //Get POST data
    $data = $request->getParams();
    $template_content = post_parser($data);
    $admin_cookie = $request->getCookieParams();

    //Update DB
    $db = $this->get('mysql');

    $id   = $args['id'];
    $path = $data['url'];

    $template_content_str = json_encode($template_content);
    $admin = $admin_cookie['venngage_admin_pass'];
    $date = date("Y-m-d H:i:s");
    
    //Allow user to update  only if the id  exists  and status is not deleted - Added By Deepa    
    $statement = $db->prepare("SELECT count(id) as count, page_status, deleted_at FROM landing_pages WHERE id = :id;"); 
    $statement->bindParam(':id', $id, PDO::PARAM_INT);
    $statement->execute();
    $result = $statement->fetch(PDO::FETCH_ASSOC);
    if ($result) {
       $count  = $result['count'];
       $page_status  = $result['page_status'];
       $deleted_at  = $result['deleted_at'];
    }          
    if ($count == 0) {
        return $response->write("The page you tried to update is invalid.")->withStatus(500);
    }
    else if ($page_status=='deleted' && $deleted_at!='') {
        return $response->write("The page you tried to update is deleted.")->withStatus(500);
    }      

    $statement = $db->prepare("UPDATE landing_pages SET template_content = :template_content, modified_at = :date, last_modified_by = :admin WHERE id = :id AND deleted_at IS NULL");

    $statement->bindParam(':template_content', $template_content_str, PDO::PARAM_STR);
    $statement->bindParam(':admin', $admin, PDO::PARAM_STR);
    $statement->bindParam(':date', $date, PDO::PARAM_STR);
    $statement->bindParam(':id', $id, PDO::PARAM_INT);

    $statement->execute();

    if ($statement->rowCount() === 1) {
        return $response->withHeader('Location', '/'.$path.'/')->withStatus(302);
    } else {
        return $response->write("Error save")->withStatus(500);
    }
  });

  $this->get('landing_page/{id}/edit/', function ($request, $response, $args) {
    $db = $this->get('mysql');
    $id = $args['id'];

    $statement = $db->prepare("SELECT `id`, `path`, `template_content`  FROM landing_pages WHERE deleted_at IS NULL AND id = :id;");
    $statement->bindParam(':id', $id, PDO::PARAM_INT);
    $statement->execute();
    $result = $statement->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $args = json_decode($result['template_content'], true);
        $args['id'] = $result['id'];
        $args['url'] = $result['path'];
    }
    $args['action'] = "/admin/landing_page/".$id."/save";
    return $this->view->render($response, 'pages/www/admin/landing_page_editor.html', $args);
  });

  //Delete Admin Cookie Function - Added By Deepa
  $this->get('landing_page/deleteadmincookie/', function ($request, $response, $args) {
      if (isset($_COOKIE['venngage_admin_pass'])) {
        unset($_COOKIE['venngage_admin_pass']);   
        setcookie('venngage_admin_pass', null, -1, '/');  
        return $response->write("Cookie deleted succesfully.")->withStatus(200); 
       } 
       else {
            return $response->write("Cookie is not set.")->withStatus(404); 
        }    
  });
  
})->add(function ($request, $response, $next) {
    $cookies = $request->getCookieParams();
    $tokens = $this->get('admin_tokens');
    $has_access = admin_check($cookies, $tokens);

    if ($has_access !== true) {
        return $this->view->render($response, 'pages/errors/404.html')->withStatus(404);
    } else {
        return $next($request, $response);
    }

});


$app->get('/{landing_page}/', function($request, $response, $args){
    $path = $args['landing_page'];

    //check for admin access
    $cookies = $request->getCookieParams();
    $tokens = $this->get('admin_tokens');
    $is_admin = admin_check($cookies, $tokens);

    if (!preg_match('/^[A-Za-z0-9_-]*$/', $path)) {
        return $this->view->render($response, 'pages/errors/404.html')->withStatus(403);
    }

    $db = $this->get('mysql');
    $statement = $db->prepare("SELECT `id`, `path`, `template_content`, `page_status`  FROM landing_pages WHERE deleted_at IS NULL AND `path` = :path;");
    $statement->bindParam(':path', $path, PDO::PARAM_STR);
    $statement->execute();
    $result = $statement->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $args = json_decode($result['template_content'], true);
        $args['id'] = $result['id'];
        $args['url'] = ucwords($result['path']);
        $args['page_status'] = $result['page_status'];
    }

    //check if page is for admin view only
    $admin_view_only = $result['page_status'] === 'draft' && $is_admin;

    if ($result['page_status'] === 'publish' || $admin_view_only) {
        return $this->view->render($response, 'pages/www/landing/landing_page_template.html', $args);
    } else {
        return $this->view->render($response, 'pages/errors/404.html')->withStatus(404);
    }
});

/**
 * Check if valid admin cookie exists
 * @param  json   $cookies    browser's cookies
 * @param  array  $tokens     array of valid tokens
 * @return boolean            TRUE if valid admin pass is found
 */
function admin_check($cookies, $tokens) {
    if (isset($cookies['venngage_admin_pass'])) {
        $cookie = $cookies['venngage_admin_pass'];
        return isset($tokens[$cookie]);
    }
    return false;
}

/**
 * Parse through POST data to be segemented JSON
 * @param  object $data   POST data object
 * @return json           example: {
 *                                      "header" : {
 *                                          "header_text": "Nonprofit Infographics Page",
 *                                          "btn": "Click Me",
 *                                          "btn_url": "venngage.com",
 *                                          "img": "image2.png"
 *                                      },
 *                                      ...
 *                               }
 */
function post_parser($data) {
    //Initialize template content
    $template_content = array();
    $section_data = array();
    $temp_key = 'header';

    //loop through POST data to convert JSON (based on section)
    foreach($data as $key => $value) {
        if ($key === 'url') {
            continue;
        }

        if ($key === 'meta' ||
            $key === 'title' ||
            $key === 'vap') {
            $template_content[$key] = $value;
            continue;
        }

        $section_key = explode("-", $key);
        $section = array_shift($section_key);
        $sub_key = array_shift($section_key);

        if ($temp_key === $section) {
            $section_data[$sub_key] = $value;
        } else {
            $template_content[$temp_key] = $section_data;
            $section_data = array( $sub_key => $value );
            $temp_key = $section;
        }
    }
    $template_content[$temp_key] = $section_data;
    return $template_content;
}

