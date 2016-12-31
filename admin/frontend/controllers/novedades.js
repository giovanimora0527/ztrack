/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
var ztrack = angular.module('ztrack');
ztrack.controller('NovedadesController', function ($rootScope, $scope, AuthService, SessionService, $state, QueriesService, toastr) {
    $scope.title = "Registro de Novedades";
    $scope.activeTab = 1;
    cargarAreas();
    $scope.novedadesselect = [];
    $scope.hayResultados = false;
    $scope.hayNovedades = false;
    $scope.btnfilteractive = "disabled";
    $scope.vehiculofilter = null;


    $scope.setActiveTab = function (tab) {
        $scope.activeTab = tab;
        if (tab === 1) {

        }
        if (tab === 2) {

        }
        if (tab === 3) {

        }

    };

    function cargarAreas() {
        $params = {
            user_id: localStorage['ztrack.user_id']
        };
        QueriesService.executeRequest('GET', '../laravel/public/grupos/cargarareas', null, $params)
                .then(function (result) {
                    $scope.areas = result.areas;
                });
    }
    ;

    $scope.onChangeSelectedArea = function () {
        cargarRutas();
    };


    function cargarRutas() {
        $params = {
            user_id: localStorage['ztrack.user_id'],
            area_id: $scope.areaselect.id
        };
        QueriesService.executeRequest('GET', '../laravel/public/rutas/rutasbyid', null, $params)
                .then(function (result) {
                    $scope.rutas = {};
                    $scope.rutas = result.rutas;
                });
    }

    $scope.cargarVehiculos = function () {
        $scope.areaselect.disabled = true;
        $scope.rutaselect.disabled = true;
        $params = {
            user_id: localStorage['ztrack.user_id'],
            ruta_id: $scope.rutaselect.route_id,
            area_id: $scope.areaselect.id
        };
        QueriesService.executeRequest('GET', '../laravel/public/novedades/vehiculos', null, $params)
                .then(function (result) {
                    $scope.vehiculos = {};
                    $scope.vehiculos = result.vehiculos;
                    if ($scope.vehiculos.length > 0) {
                        $scope.hayResultados = true;
                        toastr.success("Resultados cargados con éxito.", "OK");
                    } else {
                        $scope.hayResultados = false;
                        toastr.warning("No hay resultados de vehículos.", "Advertencia");
                    }
                });
    };

    $scope.cargarModal = function (veh) {
        $scope.vehiculoseleccionado = veh;
        $scope.cargarNovedades();
    };

    $scope.cargarNovedades = function () {
        $params = {
            user_id: localStorage['ztrack.user_id']
        };

        QueriesService.executeRequest('GET', '../laravel/public/novedades/novedades', null, $params)
                .then(function (result) {
                    $scope.novedades = {};
                    $scope.novedades = result.novedades;
                });
    };

    $scope.adicionarNovedad = function () {
        if ($scope.selectnovedad === undefined) {
            toastr.error("Debe seleccionar una novedad para continuar. Intente de nuevo", "Error");
            return;
        }
        if ($scope.novedadesselect.length === 0) {
            $scope.novedadesselect.push({
                novedad_id: $scope.selectnovedad.novedad_id,
                descripcion: $scope.selectnovedad.descripcion
            });
        } else {
            var esta = false;
            for (var i = 0; i < $scope.novedadesselect.length; i++) {
                if (parseInt($scope.novedadesselect[i].novedad_id) === parseInt($scope.selectnovedad.novedad_id)) {
                    esta = true;
                }
            }
            if (!esta) {
                $scope.novedadesselect.push({
                    novedad_id: $scope.selectnovedad.novedad_id,
                    descripcion: $scope.selectnovedad.descripcion
                });
            } else {
                toastr.warning("La novedad ya se encuentra preseleccionada. Intente de nuevo con otra.", "Atención");
            }
        }
    };

    $scope.deleteNovedad = function (item) {
        $scope.novedadesselect.splice($scope.novedadesselect.indexOf(item), 1);
    };


    $scope.registrarNovedad = function () {
        $params = {
            user_id: localStorage['ztrack.user_id'],
            despachador_id: localStorage['ztrack.despachador_id'],
            vehiculo_id: $scope.vehiculoseleccionado.object_id,
            novedades_list: $scope.novedadesselect
        };

        QueriesService.executeRequest('POST', '../laravel/public/novedades/novedadesavehiculo', $params, null)
                .then(function (result) {
                    if (result.success) {
                        $scope.novedadesselect = [];
                        $scope.selectnovedad = {};
                        $('#editarNovedad').modal('hide');
                        toastr.success(result.mensaje,"OK");
                    } else {
                        toastr.error(result.mensaje,"Error");
                    }


                });

    };

    $scope.desbloquearBtnFilter = function () {
        if ($scope.vehiculofilter !== null) {
            $scope.btnfilteractive = "";
            isfiltervehiculo = 1;
            return;
        }
        if ($scope.fechafilter === "") {
            $scope.btnfilteractive = "disabled";
        } else {
            $scope.btnfilteractive = "";
        }
    };

    $scope.limpiarCamposFilter = function () {
        $scope.btnfilteractive = "disabled";
        $scope.vehiculofilter = {};
        $scope.areaselect = {};
        $scope.rutaselect = {};
        $scope.fechafilter = "";
    };


    $scope.filtrar = function () {
        if ($scope.fechafilter !== undefined) {
            $params = {
                fecha: $scope.fechafilter                
            };
        }       
        if ($scope.vehiculofilter !== undefined && $scope.vehiculofilter !== null) {
            $params = {
                vehiculoid: $scope.vehiculofilter.object_id
            };
        }  
        if (($scope.vehiculofilter !== undefined && $scope.vehiculofilter !== null) && $scope.fechafilter !== undefined) {
            $params = {
                fecha: $scope.fechafilter,
                vehiculoid: $scope.vehiculofilter.object_id
            };
        }        
        QueriesService.executeRequest('GET', '../laravel/public/novedades/novedadesavehiculoxfiltro', null, $params)
                .then(function (result) {
                    $scope.novedadesregistradas = result.novedades;
                    if ($scope.novedadesregistradas.length > 0) {
                        toastr.success("Información cargada con éxito.", "OK");
                    } else {
                        toastr.warning("No hay resultados con el criterio de búsqueda.", "Advertencia");
                    }
                });
    };
    
    $scope.cargarModalSolucion = function(novedad){        
        $scope.novedadseleccionada = {
            descripcion : novedad.descripcion,
            id : novedad.id,
            name: novedad.name
        };        
    };
    
    
    $scope.solucionarNovedad = function(rta){
        if(rta === undefined){
            toastr.warning("Debe seleccionar si ya la novedad fue solucionada, si fue así, debe agregar una descripción de la solución","Advertencia");
            return;
        }
        if(rta.check !== undefined){            
            if(rta.descripcion === undefined){
              toastr.warning("Debe agregar una descripción de la solución para continuar.","Advertencia");
              return;
            }
            $params = {
               id : $scope.novedadseleccionada.id,
               solucionada : true,
               descripcion : rta.descripcion
            };           
        }     
        QueriesService.executeRequest('POST', '../laravel/public/novedades/solucionarnovedad', $params, null)
                .then(function (result) {
                    if(result.success){
                       toastr.success(result.mensaje, "OK"); 
                       $('#editarSolucionNovedad').modal('hide');
                    }                    
                });
    };




});

