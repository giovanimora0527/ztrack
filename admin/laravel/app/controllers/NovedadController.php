<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class NovedadController extends \BaseController {

    public function getVehiculos() {
        $data = Input::all();
        $sql = "SELECT gr.gr_id, og.group_name, guo.object_id, guo.imei, gob.name, gob.plate_number, dr.driver_name, dr.driver_id
                FROM gs_gruposrutas gr 
                JOIN gs_user_object_groups og ON og.group_id = gr.group_id
                JOIN gs_user_objects guo ON guo.group_id = og.group_id
                JOIN gs_objects gob ON gob.imei = guo.imei
                JOIN gs_user_object_drivers dr ON dr.driver_id = guo.driver_id
                WHERE gr.area_id = " . $data["area_id"] .
                " and gr.route_id = " . $data["ruta_id"] .
                " ;";

        $resultados = DB::select($sql);
        return Response::json(array('vehiculos' => $resultados));
    }

    public function getNovedades() {
        $data = Input::all();
        $sql = "select * from novedades where user_id = " . $data["user_id"] . " and tipo = 0";
        $novedades = DB::select($sql);
        return Response::json(array('novedades' => $novedades));
    }

    public function postNovedadesavehiculo() {
        $data = Input::all();
        $sql = "";
        for ($i = 0; $i < count($data["novedades_list"]); $i++) {
            $sql = "insert into registro_novedades(vehiculo_id, despachador_id, novedad_id, fecha_registro) values ("
                    . "" . $data["vehiculo_id"]
                    . "," . $data["despachador_id"]
                    . "," . $data["novedades_list"][$i]["novedad_id"]
                    . ", (select now())"
                    . ");";
            try {
                DB::beginTransaction();
                DB::insert($sql);
                DB::commit();
                if ($i == intval(count($data["novedades_list"]) - 1)) {
                    return Response::json(array('success' => true, 'mensaje' => "Las novedades han sido registradas con éxito."));
                }
            } catch (Exception $e) {
                DB::rollback();
                return Response::json(array('mensaje' => "No se pueden registrar la(s) novedad(es). Contacte al administrador de sistema. " . $e, 'error' => true));
            }
        }
    }

    public function getNovedadesavehiculoxfiltro() {
        $data = Input::all();
        $hasfecha = false;
        $hasvehiculo = false;
        if ($data["fecha"] != "") {
            $hasfecha = true;
        }
        if (isset($data["vehiculoid"])) {
            $hasvehiculo = true;
        }
        $countfilter = 0;
        //Filtrar pendiente por el usuario que creo las novedades
        $sql = "SELECT d.nombre, d.apellido, n.descripcion, rn.id, rn.vehiculo_id, gob.name, rn.fecha_registro, 
                 CASE 
                        WHEN rn.estado = 0 THEN 'Pendiente'
                        ELSE 'Solucionado'
                        END AS estado,
                 CASE 
                        WHEN rn.fecha_solucion = '0000-00-00 00:00:00' THEN 'N/R'
                        ELSE rn.fecha_solucion
                        END AS fecha_solucion
                FROM registro_novedades rn
                JOIN gs_info_despachador d ON d.user_id = rn.despachador_id
                JOIN novedades n ON rn.novedad_id = n.novedad_id  
                JOIN gs_user_objects guo ON rn.vehiculo_id = guo.object_id
                JOIN gs_objects gob ON guo.imei = gob.imei
                ";
        if ($hasvehiculo) {
            if ($countfilter == 0) {
                $sql .= " WHERE rn.vehiculo_id = " . $data["vehiculoid"];
                $countfilter++;
            } else {
                $sql .= " AND rn.vehiculo_id = " . $data["vehiculoid"];
                $countfilter++;
            }
        }
        if ($hasfecha) {
            if ($countfilter == 0) {
                $sql .= " WHERE rn.fecha_registro <= '" . $data["fecha"] . " 23:59:59'";
                $countfilter++;
            } else {
                $sql .= " AND rn.fecha_registro <= '" . $data["fecha"] . " 23:59:59'";
                $countfilter++;
            }
        }
        if ($data["active_tab"] == 2) {
            $sql .= " and rn.estado = 0 ";
        }
        $sql .= " and rn.despachador_id = " . $data["user_id"];

        $novedades = DB::select($sql);
        return Response::json(array('novedades' => $novedades));
    }

    public function postSolucionarnovedad() {
        $data = Input::all();
//        registro_novedades
        $sql = "update registro_novedades set "
                . "estado = 1"
                . ", descripcion = '" . strtoupper($data["descripcion"])
                . "', fecha_solucion = (SELECT NOW())"
                . " where id = " . $data["id"]
        ;
        
        try {
            DB::beginTransaction();
            DB::update($sql);
            DB::commit();
            return Response::json(array('success' => true, 'mensaje' => "La novedad ha sido solucionada con éxito."));
        } catch (Exception $e) {
            DB::rollback();
            return Response::json(array('mensaje' => "No se puede registrar la solución. Contacte al administrador de sistema. " . $e, 'error' => true));
        }
    }

    public function postUpdatenovedad() {
        $data = Input::all();
        $sql = "update registro_novedades set "
                . "novedad_id = " . $data["novedad_id"]
                . ", fecha_registro = '" . $data["fecha"] . " " . $data["hora"] . "'"
                . " where id = " . $data["id"];
        try {
            DB::beginTransaction();
            DB::update($sql);
            DB::commit();
            return Response::json(array('success' => true, 'mensaje' => "La novedad ha sido actualizada con éxito."));
        } catch (Exception $e) {
            DB::rollback();
            return Response::json(array('mensaje' => "No se puede actualizar la novedad. Contacte al administrador de sistema. " . $e, 'error' => true, 'success' => false));
        }
    }

    public function getVehiculosnovedades() {
        $data = Input::all();
        $sql = "select distinct guo.object_id, gob.name "
                . "from registro_novedades rn "
                . "join gs_user_objects guo ON guo.object_id = rn.vehiculo_id "
                . "join gs_objects gob ON gob.imei = guo.imei "                
                . "where rn.despachador_id = " . $data["user_id"]
                . ";"
        ;
        $results = DB::select($sql);
        return Response::json(array('success' => true, 'vehiculosnovedad' => $results));
    }

    public function postNovedadnueva() {
        $data = Input::all();
        $sql = "insert into novedades (descripcion, user_id, tipo) values ("
                . "'" . strtoupper($data["descripcion"])
                . "', " . $data["user_id"]
                . ", " . $data["tipo"] . ");";
                
        try {
            DB::beginTransaction();
            DB::insert($sql);
            DB::commit();
            return Response::json(array('success' => true, 'mensaje' => "La novedad ha sido ingresada con éxito."));
        } catch (Exception $e) {
            DB::rollback();
            return Response::json(array('mensaje' => "No se puede ingresar la novedad. Contacte al administrador de sistema. " . $e, 'error' => true, 'success' => false));
        }
    }

    public function getNovedadesadmin() {
        $data = Input::all();
        $sql = "select * from novedades where user_id = " . $data["user_id"];
        $result = DB::select($sql);
        return Response::json(array('novedadesregistradas' => $result));
    }

    public function postUpdatenovedadadmin() {
        $data = Input::all();
        $sql = "update novedades set "
                . "descripcion = '" . strtoupper($data["descripcion"])
                . "' where novedad_id = " . $data["novedad_id"];
        try {
            DB::beginTransaction();
            DB::update($sql);
            DB::commit();
            return Response::json(array('success' => true, 'mensaje' => "La novedad ha sido actualizada con éxito."));
        } catch (Exception $e) {
            DB::rollback();
            return Response::json(array('mensaje' => "No se puede actualizar la novedad. Contacte al administrador de sistema. " . $e, 'error' => true, 'success' => false));
        }
    }

    public function postDeletenovedad() {
        $data = Input::all();
        $sql = "delete from novedades where novedad_id = " . $data["novedad_id"];
        try {
            DB::beginTransaction();
            DB::delete($sql);
            DB::commit();
            return Response::json(array('success' => true, 'mensaje' => "La novedad ha sido eliminada con éxito."));
        } catch (Exception $e) {
            DB::rollback();
            return Response::json(array('mensaje' => "No se puede eliminar la novedad. Contacte al administrador de sistema. " . $e, 'error' => true, 'success' => false));
        }
    }

    public function postFiltrarresultadosnovedad() {
        $data = Input::all();
        $sql = "SELECT n.descripcion, rn.id, rn.vehiculo_id, gob.name, rn.fecha_registro, 
                 CASE 
                        WHEN rn.estado = 0 THEN 'Pendiente'
                        ELSE 'Solucionado'
                        END AS estado,
                 CASE 
                        WHEN rn.fecha_solucion = '0000-00-00 00:00:00' THEN 'N/R'
                        ELSE rn.fecha_solucion
                        END AS fecha_solucion
                FROM registro_novedades rn                
                JOIN novedades n ON rn.novedad_id = n.novedad_id  
                JOIN gs_user_objects guo ON rn.vehiculo_id = guo.object_id
                JOIN gs_objects gob ON guo.imei = gob.imei
                ";
        $countfilter = 0;
        if (isset($data["vehiculo"])) {
            if ($countfilter == 0) {
                $sql .= " WHERE rn.vehiculo_id = " . $data["vehiculo"]["object_id"];
                $countfilter++;
            } else {
                $sql .= " AND rn.vehiculo_id = " . $data["vehiculo"]["object_id"];
                $countfilter++;
            }
        }
        if (isset($data["novedad"])) {
            if ($countfilter == 0) {
                $sql .= " WHERE rn.novedad_id = " . $data["novedad"]["novedad_id"];
                $countfilter++;
            } else {
                $sql .= " AND rn.novedad_id = " . $data["novedad"]["novedad_id"];
                $countfilter++;
            }
        }
        if (isset($data["filtrofecha"])) {
            if ($countfilter == 0) {
                $sql .= " WHERE rn.fecha_registro <= '" . $data["filtrofecha"] . " 23:59:59'";
                $countfilter++;
            } else {
                $sql .= " AND rn.novedad_id <= '" . $data["filtrofecha"] . " 23:59:59'";
                $countfilter++;
            }
        }
        if (isset($data["fechasolucion"])) {
            if ($countfilter == 0) {
                $sql .= " WHERE rn.fecha_solucion <= '" . $data["fechasolucion"] . " 23:59:59'";
                $countfilter++;
            } else {
                $sql .= " AND rn.fecha_solucion <= '" . $data["fechasolucion"] . " 23:59:59'";
                $countfilter++;
            }
        }
        if ($data["tab"] == 3) {
            if (isset($data["filtroestado"])) {
                if ($countfilter == 0) {
                    $sql .= " WHERE rn.estado = " . $data["filtroestado"];
                    $countfilter++;
                } else {
                    $sql .= " AND rn.estado = " . $data["filtroestado"];
                    $countfilter++;
                }
            }
        }
        if ($data["tab"] == 2) {            
                if ($countfilter == 0) {
                    $sql .= " WHERE rn.estado = 0";
                    $countfilter++;
                } else {
                    $sql .= " AND rn.estado = 0";
                    $countfilter++;
                }            
        }
        $sql .= " and rn.despachador_id = " . $data["user_id"];
        
        try {
            DB::beginTransaction();
            $novedades = DB::select($sql);
            DB::commit();
            return Response::json(array('novedades' => $novedades, 'success' => true));       
        } catch (Exception $e) {
            DB::rollback();
            return Response::json(array('mensaje' => "Error. Contacte al administrador de sistema. " . $e, 'error' => true, 'success' => false));
        } 
    }
    
    public function getNovedadesconductores() {
        $data = Input::all();
        $sql = "select * from novedades where user_id = " . $data["user_id"] . " and tipo = 1";
        $novedades = DB::select($sql);
        return Response::json(array('novedades' => $novedades));
    }
    
    public function postNovedadesaconductor() {
        $data = Input::all();
        $sql = "";
        for ($i = 0; $i < count($data["novedades_list"]); $i++) {
            $sql = "insert into novedades_conductores(conductor_id, despachador_id, novedad_id, fecha_registro) values ("
                    . "" . $data["conductor_id"]
                    . "," . $data["despachador_id"]
                    . "," . $data["novedades_list"][$i]["novedad_id"]
                    . ", (select now())"
                    . ");";
            try {
                DB::beginTransaction();
                DB::insert($sql);
                DB::commit();
                if ($i == intval(count($data["novedades_list"]) - 1)) {
                    return Response::json(array('success' => true, 'mensaje' => "Las novedades han sido registradas con éxito."));
                }
            } catch (Exception $e) {
                DB::rollback();
                return Response::json(array('mensaje' => "No se pueden registrar la(s) novedad(es). Contacte al administrador de sistema. " . $e, 'error' => true));
            }
        }
    }
    
    
    public function getNovedadesaconductorxfiltro() {
        $data = Input::all();
        $hasfecha = false;        
        if ($data["fecha"] != "") {
            $hasfecha = true;
        }
        
        $countfilter = 0;
        //Filtrar pendiente por el usuario que creo las novedades
        $sql = "SELECT dr.driver_name, n.descripcion, rn.id, rn.conductor_id, rn.fecha_registro, 
                 CASE 
                        WHEN rn.estado = 0 THEN 'Pendiente'
                        ELSE 'Solucionado'
                        END AS estado,
                 CASE 
                        WHEN rn.fecha_solucion = '0000-00-00 00:00:00' THEN 'N/R'
                        ELSE rn.fecha_solucion
                        END AS fecha_solucion
                FROM novedades_conductores rn                
                JOIN novedades n ON rn.novedad_id = n.novedad_id 
                JOIN gs_user_object_drivers dr ON dr.driver_id = rn.conductor_id
                ";        
        if ($hasfecha) {
            if ($countfilter == 0) {
                $sql .= " WHERE rn.fecha_registro <= '" . $data["fecha"] . " 23:59:59'";
                $countfilter++;
            } else {
                $sql .= " AND rn.fecha_registro <= '" . $data["fecha"] . " 23:59:59'";
                $countfilter++;
            }
        }
        if ($data["active_tab"] == 2) {
            $sql .= " and rn.estado = 0 ";
        }
        
        $sql .= " and rn.despachador_id = " . $data["user_id"];
       
        $novedades = DB::select($sql);
        return Response::json(array('novedades' => $novedades));
    }
    
    public function getConductoresnovedades() {
        $data = Input::all();
        $sql = "select distinct rn.novedad_id, dr.driver_name, dr.driver_id "
                . "from novedades_conductores rn "
                . "JOIN gs_user_object_drivers dr ON dr.driver_id = rn.conductor_id "               
                . "where rn.despachador_id = " . $data["user_id"]
                . ";"
        ;       
        $results = DB::select($sql);
        return Response::json(array('success' => true, 'conductoresnovedad' => $results));
    }
    
    public function postFiltrarresultadosnovedadconductores() {
        $data = Input::all();
        $sql = "SELECT dr.driver_name, n.descripcion, rn.id, rn.conductor_id, rn.fecha_registro, 
                 CASE 
                        WHEN rn.estado = 0 THEN 'Pendiente'
                        ELSE 'Solucionado'
                        END AS estado,
                 CASE 
                        WHEN rn.fecha_solucion = '0000-00-00 00:00:00' THEN 'N/R'
                        ELSE rn.fecha_solucion
                        END AS fecha_solucion
                FROM novedades_conductores rn                
                JOIN novedades n ON rn.novedad_id = n.novedad_id  
                JOIN gs_user_object_drivers dr ON dr.driver_id = rn.conductor_id
                ";
        $countfilter = 0;
        if (isset($data["conductor"])) {
            if ($countfilter == 0) {
                $sql .= " WHERE rn.conductor_id = " . $data["conductor"]["driver_id"];
                $countfilter++;
            } else {
                $sql .= " AND rn.conductor_id = " . $data["conductor"]["driver_id"];
                $countfilter++;
            }
        }
        if (isset($data["novedad"])) {
            if ($countfilter == 0) {
                $sql .= " WHERE rn.novedad_id = " . $data["novedad"]["novedad_id"];
                $countfilter++;
            } else {
                $sql .= " AND rn.novedad_id = " . $data["novedad"]["novedad_id"];
                $countfilter++;
            }
        }
        if (isset($data["filtrofecha"])) {
            if ($countfilter == 0) {
                $sql .= " WHERE rn.fecha_registro <= '" . $data["filtrofecha"] . " 23:59:59'";
                $countfilter++;
            } else {
                $sql .= " AND rn.fecha_registro <= '" . $data["filtrofecha"] . " 23:59:59'";
                $countfilter++;
            }
        }
        if (isset($data["fechasolucion"])) {
            if ($countfilter == 0) {
                $sql .= " WHERE rn.fecha_solucion <= '" . $data["fechasolucion"] . " 23:59:59'";
                $countfilter++;
            } else {
                $sql .= " AND rn.fecha_solucion <= '" . $data["fechasolucion"] . " 23:59:59'";
                $countfilter++;
            }
        }
        if ($data["tab"] == 3) {
            if (isset($data["filtroestado"])) {
                if ($countfilter == 0) {
                    $sql .= " WHERE rn.estado = " . $data["filtroestado"];
                    $countfilter++;
                } else {
                    $sql .= " AND rn.estado =  " . $data["filtroestado"];
                    $countfilter++;
                }
            }
        }
        if ($data["tab"] == 2) {            
                if ($countfilter == 0) {
                    $sql .= " WHERE rn.estado = 0";
                    $countfilter++;
                } else {
                    $sql .= " AND rn.estado = 0";
                    $countfilter++;
                }            
        }
        $sql .= " and rn.despachador_id = " . $data["user_id"];
                
        try {
            DB::beginTransaction();
            $novedades = DB::select($sql);
            DB::commit();
            return Response::json(array('novedades' => $novedades, 'success' => true));       
        } catch (Exception $e) {
            DB::rollback();
            return Response::json(array('mensaje' => "Error. Contacte al administrador de sistema. " . $e, 'error' => true, 'success' => false));
        } 
    }
    
     public function postUpdatenovedadconductor() {
        $data = Input::all();
        $sql = "update novedades_conductores set "
                . "novedad_id = " . $data["novedad_id"]
                . ", fecha_registro = '" . $data["fecha"] . " " . $data["hora"] . "'"
                . " where id = " . $data["id"];
        try {
            DB::beginTransaction();
            DB::update($sql);
            DB::commit();
            return Response::json(array('success' => true, 'mensaje' => "La novedad ha sido actualizada con éxito."));
        } catch (Exception $e) {
            DB::rollback();
            return Response::json(array('mensaje' => "No se puede actualizar la novedad. Contacte al administrador de sistema. " . $e, 'error' => true, 'success' => false));
        }
    }
    
    public function postSolucionarnovedadconductor() {
        $data = Input::all();
        $sql = "update novedades_conductores set "
                . "estado = 1"
                . ", descripcion = '" . strtoupper($data["descripcion"])
                . "', fecha_solucion = (SELECT NOW())"
                . "' where id = " . $data["id"]
        ;
        try {
            DB::beginTransaction();
            DB::update($sql);
            DB::commit();
            return Response::json(array('success' => true, 'mensaje' => "La novedad ha sido solucionada con éxito."));
        } catch (Exception $e) {
            DB::rollback();
            return Response::json(array('mensaje' => "No se puede registrar la solución. Contacte al administrador de sistema. " . $e, 'error' => true));
        }
    }

}
