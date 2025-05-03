import $ from "jquery"
function changeRqCls () {
    const rqcls = ($(this).find("option:selected").attr("data-rqcls") === "true") ? true : false

    const element = $("#user_roles_effectiveStudentClass").parent();
    const oldRqcls = element.data("rqcls")
    const timeout = (oldRqcls === undefined) ? 0 : 300
    element.data("rqcls", rqcls)
    if (rqcls !== oldRqcls) {
        if (rqcls) {
            element.show(timeout)
        } else {
            element.hide(timeout)
        }
    }
}

$('#user_roles_effectiveRole').bind("change", changeRqCls).each(changeRqCls)
