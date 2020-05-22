var mmg;
function initSaleGrid(){
    var laydate = layui.laydate;
    laydate.render({
        elem: '#startDate'
    });
    laydate.render({
        elem: '#endDate'
    });
    var h = WST.pageHeight();
    var cols = [
            {title:'&nbsp;', name:'goodsName', width: 30, renderer: function(val,item,rowIndex){
            	var thumb = item['goodsImg'];
                return "<span class='weixin'><img id='img' onmouseout='toolTip()' onmouseover='toolTip()' style='height:60px;width:60px;' src='"+thumb
            	+"'><span class='imged' ><img  style='height:180px;width:180px;' src='"+item['goodsImg']+"'></span></span>";
            }},
            {title:'商品名称', name:'goodsName', width: 430, renderer: function(val,item,rowIndex){
                return item['goodsName'];
            }},
            {title:'商品编号', name:'goodsSn', width: 80},
            {title:'所属店铺', name:'shopName', width: 150},
            {title:'销量', name:'goodsNum' , width: 20}
            ];
 
    mmg = $('.mmg').mmGrid({height: (h-85),indexCol: true,indexColWidth:50,  cols: cols,method:'POST',
        url: WST.U('admin/reports/topSaleGoodsByPage',WST.getParams('.ipt')), fullWidthRows: true, autoLoad: true,
        plugins: [
            $('#pg').mmPaginator({})
        ]
    });  
}
function loadGrid(){
	var params = WST.getParams('.ipt');
    params.page = 1;
	mmg.load(params);
}
function toolTip(){
    WST.toolTip();
}