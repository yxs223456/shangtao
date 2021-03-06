var mmg;
function initGrid(){
    var h = WST.pageHeight();
    var cols = [
            {title:'银行名称', name:'bankName', width: 100},
            {title:'操作', name:'' ,width:70, align:'center', renderer: function(val,item,rowIndex){
                var h = "";
                if(WST.GRANT.YHGL_02)h += "<a  class='btn btn-blue' onclick='javascript:getForEdit("+item['bankId']+")'><i class='fa fa-pencil'></i>修改</a> ";
                if(WST.GRANT.YHGL_03)h += "<a  class='btn btn-red' onclick='javascript:toDel(" + item['bankId'] + ")'><i class='fa fa-trash-o'></i>删除</a> ";
                return h;
            }}
            ];
 
    mmg = $('.mmg').mmGrid({height: h-80,indexCol: true, cols: cols,method:'POST',
        url: WST.U('admin/banks/pageQuery'), fullWidthRows: true, autoLoad: true,
        plugins: [
            $('#pg').mmPaginator({})
        ]
    });  
}
function toDel(id){
	var box = WST.confirm({content:"您确定要删除该记录吗?",yes:function(){
	           var loading = WST.msg('正在提交数据，请稍后...', {icon: 16,time:60000});
	           	$.post(WST.U('admin/banks/del'),{id:id},function(data,textStatus){
	           			  layer.close(loading);
	           			  var json = WST.toAdminJson(data);
	           			  if(json.status=='1'){
	           			    	WST.msg("操作成功",{icon:1});
	           			    	layer.close(box);
	           		            mmg.load();
	           			  }else{
	           			    	WST.msg(json.msg,{icon:2});
	           			  }
	           		});
	            }});
}

function getForEdit(id){
	 var loading = WST.msg('正在获取数据，请稍后...', {icon: 16,time:60000});
     $.post(WST.U('admin/banks/get'),{id:id},function(data,textStatus){
           layer.close(loading);
           var json = WST.toAdminJson(data);
           if(json.bankId){
           		WST.setValues(json);
           		toEdit(json.bankId);
           }else{
           		WST.msg(json.msg,{icon:2});
           }
    });
}

function toEdit(id){
	var title =(id==0)?"新增":"编辑";
	var box = WST.open({title:title,type:1,content:$('#bankBox'),area: ['450px', '160px'],
		btn:['确定','取消'],end:function(){$('#bankBox').hide();},yes:function(){
		$('#bankForm').submit();
	}});
	$('#bankForm').validator({
        fields: {
            bankName: {
            	rule:"required;",
            	msg:{required:"银行名称不能为空"},
            	tip:"请输入银行名称",
            	ok:"",
            },
           
        },
       valid: function(form){
		        var params = WST.getParams('.ipt');
	                params.bankId = id;
	                var loading = WST.msg('正在提交数据，请稍后...', {icon: 16,time:60000});
	           		$.post(WST.U('admin/banks/'+((id==0)?"add":"edit")),params,function(data,textStatus){
	           			  layer.close(loading);
	           			  var json = WST.toAdminJson(data);
	           			  if(json.status=='1'){
	           			    	WST.msg("操作成功",{icon:1});
	           			    	$('#bankBox').hide();
	           			    	$('#bankForm')[0].reset();
	           			    	layer.close(box);
	           		            mmg.load();
	           			  }else{
	           			        WST.msg(json.msg,{icon:2});
	           			  }
	           		});

    	}

  });

}