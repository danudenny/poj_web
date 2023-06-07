import bootstrap from "../../data/bootsrap.json"
import datatable from "../../data/datatable.json"

const state = {
	bootstrap: bootstrap.tableData1,
    inverse: bootstrap.inverse,
    hoverable: bootstrap.hoverable,
	users: bootstrap.users,
	roles: bootstrap.roles,
	table: bootstrap.table2,
	sizingTablesXl: bootstrap.sizingTablesXl,
	Responsive:bootstrap.Responsive,
	tableData: bootstrap.tableData,
	tableData2: bootstrap.tableData2,
	datatable:datatable.Item
};

export default {
	namespaced: true,
	state,
};