/**
 * Emergency Sowing API Client
 * Provides functions to fetch and approve emergency sowing provisional sales orders.
 */
const EmergencySowingAPI = {
    baseUrl: 'modal/emergencysowing_modal.php',

    /**
     * Helper to read context data from DOM
     */
    getContext: function () {
        const $dashboard = $('.emergency-dashboard');
        const $body = $('body');
        return {
            projectid: $dashboard.data('projectid') || $body.data('projectid') || (window.EMERGENCY_CONTEXT ? window.EMERGENCY_CONTEXT.projectid : '') || '',
            companyid: $dashboard.data('companyid') || $body.data('companyid') || (window.EMERGENCY_CONTEXT ? window.EMERGENCY_CONTEXT.companyid : '') || '',
            subcompanyid: $dashboard.data('subcompanyid') || $body.data('subcompanyid') || (window.EMERGENCY_CONTEXT ? window.EMERGENCY_CONTEXT.subcompanyid : '') || '',
            loginroleid: $dashboard.data('loginroleid') || $body.data('loginroleid') || (window.EMERGENCY_CONTEXT ? window.EMERGENCY_CONTEXT.loginroleid : 0) || 0,
            loginid: $dashboard.data('loginid') || $body.data('loginid') || (window.EMERGENCY_CONTEXT ? window.EMERGENCY_CONTEXT.loginid : '') || '',
            loginname: $dashboard.data('loginname') || $body.data('loginname') || (window.EMERGENCY_CONTEXT ? window.EMERGENCY_CONTEXT.loginname : '') || ''
        };
    },

    /**
     * Fetch all provisional sales orders pending for emergency sowing approval
     * @param {Object} params - Context params (projectid, companyid, subcompanyid, loginroleid, loginid)
     * @returns {Promise<Object>}
     */
    fetchPendingOrders: function (params = {}) {
        const defaultParams = this.getContext();
        const payload = Object.assign({}, defaultParams, params);

        return $.ajax({
            url: this.baseUrl + '?action=get_emergency_sowing_orders',
            type: 'POST',
            data: payload,
            dataType: 'json'
        });
    },

    /**
     * Approve an emergency sowing order and transition status to 'Draft'
     * @param {string} psono - Provisional Sales Order Number
     * @param {number|string} projectid - Project ID
     * @param {string} loginid - Current user login id
     * @param {string} approverName - Current user display name
     * @returns {Promise<Object>}
     */
    approveOrder: function (psono, projectid, loginid, approverName = '') {
        const ctx = this.getContext();
        const projId = projectid || ctx.projectid;
        const usrId = loginid || ctx.loginid;
        const usrName = approverName || ctx.loginname || usrId;

        return $.ajax({
            url: this.baseUrl + '?action=approve_emergency_sowing',
            type: 'POST',
            data: {
                psono: psono,
                projectid: projId,
                loginid: usrId,
                approver_name: usrName
            },
            dataType: 'json'
        });
    }
};

if (typeof window !== 'undefined') {
    window.EmergencySowingAPI = EmergencySowingAPI;
}
