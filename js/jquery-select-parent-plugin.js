import $ from "jquery"

$.fn.selectParent = function (selector, includeSelf = false) {
    var p = includeSelf ? this : this.parent()
    while (p.length > 0) {
        if (p.is(selector)) {
            return p
        }
        p = p.parent()
    }
    return p
}
