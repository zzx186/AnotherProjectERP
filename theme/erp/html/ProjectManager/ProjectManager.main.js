var currentTab;

$(document).on('shown.bs.tab', 'a[data-toggle="tab"]', function (e) {

    var curID = e.target.id;

    genTabContent(curID);
});

function genTabContent(tab) {

    currentTab = tab;
    mwce.genIn({
        element:'tab_content',
        address:'|site|page/|currentPage|/'+currentTab,
        loadicon:'<div style="width: 100%; text-align: center;color:green; margin-top:100px;">Загружаю...</div>',
        callback:function () {
            switch (currentTab){
                case 'TabsManagement':
                    showTabsCfg(document.querySelector('#TabChosen').value);
                    break;

            }
        }
    });
}

function getStagesList() {
    mwce.genIn({
        element:'prStageBody',
        address:'|site|page/|currentPage|/GetStageList',
        loadicon:'<tr><td colspan="3" style="text-align: center;color:green;">Загружаю...</td></tr>'
    });
}
function editStage(id) {
    $('#forDialogs').dialog({
        title:'Редактировать стадию',
        modal:true,
        resizable:false,
        width:490,
        buttons:{
            'Сохранить':function () {
                if(document.querySelector('#stageName_').value.trim().length >0){
                    mwce.genIn({
                        noresponse:true,
                        before:function () {
                            document.querySelector('#forDialogs').style.opacity = 0.2;
                        },
                        address:'|site|page/|currentPage|/StageEdit?id='+id,
                        type:'POST',
                        data:$('#stageEform').serialize(),
                        callback:function () {
                            getStagesList();
                            $('#forDialogs').dialog('close');
                        }
                    });
                }
                else{
                    mwce.alert('Не введено название','Сообщение');
                }
            },
            'Закрыть':function () {
                $(this).dialog('close');
            }
        },
        open:function () {
            document.querySelector('#forDialogs').style.opacity = 1;
            mwce.genIn({
                element:'forDialogs',
                address:'|site|page/|currentPage|/StageEdit?id='+id,
                loadicon:'Загружаю...'
            });
        },
        close:function () {
            $(this).dialog('destroy');
        }
    });
}
function editStageAccess(id) {
    $('#forDialogs').dialog({
        title:'Редактировать доступ к стадии',
        modal:true,
        resizable:false,
        width:490,
        buttons:{
            'Сохранить':function () {
                mwce.genIn({
                    noresponse: true,
                    address: '|site|page/|currentPage|/StageAccessEdit?id=' + id+'&curGrp=' + document.querySelector('#curGrp').value,
                    type: 'POST',
                    data: $('#editAccessStageF').serialize(),
                    callback: function () {
                        $('#forDialogs').dialog('close');
                    }
                });
            },
            'Закрыть':function () {
                $(this).dialog('close');
            }
        },
        open:function () {
            document.querySelector('#forDialogs').style.opacity = 1;
            mwce.genIn({
                element:'forDialogs',
                address:'|site|page/|currentPage|/StageAccessEdit?id='+id,
                loadicon:'Загружаю...'
            });
        },
        close:function () {
            $(this).dialog('destroy');
        }
    });
}
function editStageAccessRole(stage,group) {
    mwce.genIn({
        element:'editAccessStageF',
        address:'|site|page/|currentPage|/GetStageRespUsers?id='+stage+'&curGrp='+group,
        loadicon:'Загружаю...'
    })
}
function addStage() {
    $('#forDialogs').dialog({
        title:'Добавить стадию',
        modal:true,
        resizable:false,
        width:490,
        buttons:{
            'Добавить':function () {
                if(document.querySelector('#stageName_').value.trim().length >0){
                    mwce.genIn({
                        noresponse:true,
                        before:function () {
                            document.querySelector('#forDialogs').style.opacity = 0.2;
                        },
                        address:'|site|page/|currentPage|/StageAdd',
                        type:'POST',
                        data:$('#stageEform').serialize(),
                        callback:function () {
                            getStagesList();
                            $('#forDialogs').dialog('close');
                        }
                    });
                }
                else{
                    mwce.alert('Не введено название','Сообщение');
                }
            },
            'Закрыть':function () {
                $(this).dialog('close');
            }
        },
        open:function () {
            document.querySelector('#forDialogs').style.opacity = 1;
            mwce.genIn({
                element:'forDialogs',
                address:'|site|page/|currentPage|/StageAdd',
                loadicon:'Загружаю...'
            });
        },
        close:function () {
            $(this).dialog('destroy');
        }
    });
}
function delStage(id) {
    mwce.confirm({
        title:'Требуется решение',
        text:'Вы действительно хотите удалить стадию?',
        buttons:{
            'Да':function () {
                mwce.genIn({
                    noresponse:true,
                    address:'|site|page/|currentPage|/DeleteSage?id='+id,
                    before:function () {
                        document.querySelector('#for_mwce_confirm').style.opacity = 0.2;
                    },
                    callback:function () {
                        getStagesList();
                        mwce.confirm.close();
                    }
                });
            },
            'Нет':function () {
                mwce.confirm.close();
            }
        }
    });
}


function saveCfg() {

    mwce.confirm({
        title:'Требуется решение',
        text:'Вы уверены, что хотите сохранить конфигурацию?',
        buttons:{
            'Да':function () {
                mwce.genIn({
                    noresponse:true,
                    address:'|site|page/|currentPage|/SaveProjecrCfg',
                    type:'POST',
                    data:$('#projectCfgForm').serialize(),
                    before:function () {
                        document.querySelector('#projectCfgForm').style.opacity = 0.5;
                        document.querySelector('#statusIds').innerHTML='Сохраняю...';
                    },
                    callback:function () {
                        document.querySelector('#projectCfgForm').style.opacity = 1;
                        document.querySelector('#statusIds').innerHTML='';
                    }
                });
                mwce.confirm.close();
            },
            'Нет':function () {
                mwce.confirm.close();
            }
        }
    });
}

/**
 * настройка вкладок
 */
function showTabsCfg(tabName) {
    mwce.genIn({
        element:'tabsCfgList',
        address:'|site|page/|currentPage|/TabCfg',
        type:"POST",
        data:'TabChosen='+tabName,
        loadicon:'<tr><td colspan="2" style="text-align: center; color:green">Загружаю...</td></tr>'
    })
}
function saveTabCfg() {

    mwce.confirm({
        title:'Требуется решение',
        text:'Вы уверены, что хотите сохранить конфигурацию?',
        buttons:{
            'Да':function () {
                mwce.genIn({
                    noresponse:true,
                    address:'|site|page/|currentPage|/SaveTabCfg?tab='+document.querySelector('#TabChosen').value,
                    type:'POST',
                    data:$('#configCustomForm').serialize(),
                    before:function () {
                        document.querySelector('#configCustomForm').style.opacity = 0.5;
                    },
                    callback:function () {
                        document.querySelector('#configCustomForm').style.opacity = 1;
                    }
                });
                mwce.confirm.close();
            },
            'Нет':function () {
                mwce.confirm.close();
            }
        }
    });
}

function addNewDocGroup(){
    $('#forDialogs').dialog({
        title: 'Добавить новую группу документов',
        modal: true,
        resizable: false,
        width: 350,
        buttons: {
            'Добавить':function () {
                mwce.genIn({
                    noresponse:true,
                    address:'|site|page/|currentPage|/AddDocGroup',
                    type:'POST',
                    data:$('#addDocsForm').serialize(),
                    callback:function () {
                        $('#forDialogs').dialog('close');
                        getDocGroups();
                    }
                })

            },
            'Закрыть':function () {
                $(this).dialog('close');
            }
        },
        open:function () {
            mwce.genIn({
                element:'forDialogs',
                address:'|site|page/|currentPage|/AddDocGroup',
                loadicon:'Загружаюсь...'
            })
        },
        close:function () {
            $(this).dialog('destroy');
        }
    });
}
function getDocGroups() {
    mwce.genIn({
        element:'docGroupLists',
        address:'|site|page/|currentPage|/GetDocGroups',
        loadicon:'<tr><td colspan="2">Загружаюсь...</td></tr>'
    })
}

function delDocGroup(id) {
    mwce.confirm({
        title:'Удаление группы',
        text:'Вы действительно хотите удалить группу документов?',
        buttons:{
            'Да':function () {
                mwce.genIn({
                    noresponse:true,
                    address:'|site|page/|currentPage|/DelDocGroup?id='+id,
                    callback:function () {
                        document.querySelector('#docGIdPos_'+id).remove();
                        mwce.confirm.close();
                    }
                })
            },
            'Нет':function () {
                mwce.confirm.close();
            }
        }
    })
}
function editDocGroup(id) {
    $('#forDialogs').dialog({
        title:'Изменить доступы',
        width:500,
        modal:true,
        resizable:false,
        open:function () {
            mwce.genIn({
                element:'forDialogs',
                loadicon:'Загружаю...',
                address:'|site|page/|currentPage|/EditDocGroup?id='+id
            });
        },
        close: function () {
            $(this).dialog('destroy');
        },
        buttons:{
            'Применить':function () {
                mwce.genIn({
                    noresponse:true,
                    address:'|site|page/|currentPage|/EditDocGroup?id='+id,
                    type:'POST',
                    data: $('#PrDocsEdit').serialize(),
                    callback:function () {
                        $('#forDialogs').dialog('close');
                    }
                });
            },
            'Закрыть':function () {
                $(this).dialog('close');
            }

        }
    });
}

$(document).ready(function(){
    genTabContent('GetStageForm');
});