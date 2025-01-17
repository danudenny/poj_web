import {createRouter, createWebHistory} from "vue-router"
import Body from '../components/body.vue';
import Default from '../pages/dashboard/defaultPage.vue';
import store from '../store';
import {useToast} from "vue-toastification";

/* Auth */
import login from '../auth/login.vue';
import forgetPassword from '../auth/forget_password.vue';
import resetPassword from '../auth/reset_password.vue';

// Users Management
import Users from '../pages/users/index.vue';
import UserEdit from '../pages/users/edit.vue';
import UserDetail from '../pages/users/details.vue';
import UserAdd from '../pages/users/create.vue';

// Roles Management
import Roles from '../pages/roles/index.vue';
import RoleAdd from '../pages/roles/create.vue';
import RoleEdit from '../pages/roles/edit.vue';
import RoleDetail from '../pages/roles/details.vue';

// Permission Management
import Permissions from '../pages/permissions/index.vue';
import PermissionAdd from '../pages/permissions/create.vue';
import PermissionEdit from '../pages/permissions/edit.vue';
import PermissionDetail from '../pages/permissions/details.vue';

// Employee Management
import Employees from '../pages/employees/index.vue';
import EmployeeDetail from '../pages/employees/details.vue';

// General Settings
import GeneralSettings from '../pages/settings/index.vue';
import Syncs from '../pages/syncs/index.vue'
// Work Area Settings
import Cabang from '../pages/cabang/index.vue';
import CabangDetail from '../pages/cabang/details.vue';

// Master Data
import TimesheetSchedule from '../pages/timesheet-schedule/index.vue';
import TimesheetScheduleCreate from '../pages/timesheet-schedule/create.vue';
import TimesheetScheduleEdit from '../pages/timesheet-schedule/edit.vue';
import TimesheetScheduleDetail from '../pages/timesheet-schedule/details.vue';

import Approval from '../pages/approvals/index.vue';
import ApprovalModule from '../pages/approval-modules/index.vue';
import CreateApproval from '../pages/approvals/Create.vue';
import EditApproval from '../pages/approvals/Edit.vue';
import DetailApproval from '../pages/approvals/Detail.vue';
import Department from '../pages/departments/index.vue';
import DepartmentEdit from '../pages/departments/update.vue';
import Regional from '../pages/regionals/index.vue';
import RegionalDetail from '../pages/regionals/details.vue';

//  Attendances
import Attendances from '../pages/attendances/index.vue';
import DetailAttendance from '../pages/attendances/detail.vue';
import AttendanceApproval from '../pages/attendances/list-approval.vue';
import Backup from '../pages/backups/index.vue';
import CreateBackup from '../pages/backups/create.vue';
import DetailBackup from '../pages/backups/detail.vue';
import EmployeeBackup from '../pages/backups/employee-backup.vue';
import ListApprovalBackup from '../pages/backups/list-approval.vue';

// Profile
import Profile from '../pages/profiles/index.vue';

//Corporate
import Corporates from '../pages/corporate/index.vue';
import CorporateDetail from '../pages/corporate/details.vue';

//Kanwil
import Kanwils from '../pages/kanwil/index.vue';
import KanwilDetail from '../pages/kanwil/details.vue';

//Area
import Areas from '../pages/area/index.vue';
import AreaDetail from '../pages/area/details.vue';

//Outlet
import Outlets from '../pages/outlet/index.vue';
import OutletDetail from '../pages/outlet/details.vue';

//Incident Reporting
import IncidentReporting from '../pages/incident-reporting/index.vue';
import IncidentReportingDetail from '../pages/incident-reporting/detail.vue';

//Event
import EventRequestList from '../pages/event/index.vue';
import EventRequestDetail from '../pages/event/detail.vue';
import EventRequestCreate from '../pages/event/create.vue';
import EventEmployeeEvent from '../pages/event/employee-event.vue';
import EventApproval from '../pages/event/event-approval.vue';

//Work Reporting
import WorkReporting from '../pages/work_reporting/index.vue';
import WorkReportingDetail from '../pages/work_reporting/detail.vue';

// Overtime
import Overtime from "../pages/overtime/index.vue";
import CreateOvertime from "../pages/overtime/create.vue";
import DetailOvertime from "../pages/overtime/detail.vue";
import EmployeeOvertime from "../pages/overtime/employee-overtime.vue";
import ListApprovalOvertime from "../pages/overtime/list-approval.vue";

// Leave
import LeaveRequest from "../pages/leaves/leave-request.vue";
import LeaveRequestDetail from "../pages/leaves/details/leave-request-detail.vue";
import LeaveApproval from "../pages/leaves/leave-approval.vue";
import LeaveMaster from "../pages/leaves/leave-master.vue";

// Admin Unit
import AdminUnit from "../pages/admin-unit/index.vue";

// Job
import Job from "../pages/jobs/index.vue";
import JobEdit from "../pages/jobs/edit.vue";
import JobCanvas from "../pages/jobs/canvas.vue";

// Team
import Team from "../pages/teams/index.vue";
import TeamEdit from "../pages/teams/update.vue";
import TeamDetail from "../pages/teams/details.vue";

// Public Holiday
import PublicHoliday from "../pages/public-holiday/index.vue";

// Timesheet Reporting
import TimesheetReportingIndex from "../pages/timesheet-reporting/index.vue";
import TimesheetReportingDetail from "../pages/timesheet-reporting/detail.vue";
import TimesheetReportingCreate from "../pages/timesheet-reporting/create.vue";
import EmployeeTimesheetReporting from "../pages/timesheet-reporting/employee.vue";

//Error page
import Error from  '../components/error.vue';
import UnitJob from "../pages/jobs/unit-job.vue";

const routes =[
    {
        path: '/',
        component: Body,

        children: [
          {
            path: '',
            name: 'defaultRoot',
            component: Default,
            meta: {
              title: 'POJ - Dashboard',
              requiresAuth: true,
              permission : 'dashboard-read'
            }
          },

        ]
    },
    {
      path: '/auth',
      children: [
        {
          path: 'login',
          name: 'Login',
          component: login,
          meta: {
            title: 'POJ - Login',
            requiresAuth: false,
          }
        },
        {
          path: 'forget_password',
          name: 'Forget Password',
          component: forgetPassword,
          meta: {
            title: 'POJ - Forget Password',
            requiresAuth: false,
          }
        },
        {
          path: 'reset_password',
          name: 'Reset Password',
          component: resetPassword,
          meta: {
            title: 'POJ - Reset Password',
            requiresAuth: false,
          }
        }

      ]
    },
    {
      path: '/management',
      component: Body,
      children: [
        {
            path: 'users',
            name: 'Users Management',
            component: Users,
            meta: {
                title: 'POJ - Users Management',
                requiresAuth: true,
                permission : 'user-read',
            },
        },
        {
              path: 'users/create',
              name: 'User Create',
              component: UserAdd,
              meta: {
                  title: 'POJ - User Create',
                  requiresAuth: true,
                  permission : 'user-create',
              }
        },
        {
            path: 'users/detail/:id',
            name: 'user-detail',
            component: UserDetail,
            meta: {
                title: 'POJ - User Detail',
                requiresAuth: true,
                permission : 'user-read',
            }
        },
        {
            path: 'users/edit/:id',
            name: 'user-edit',
            component: UserEdit,
            meta: {
                title: 'POJ - User Edit',
                requiresAuth: true,
                permission : 'user-update',
            }
        },
        {
            path: 'roles',
            name: 'Roles Management',
            component: Roles,
            meta: {
                title: 'POJ - Roles Management',
                requiresAuth: true,
                permission : 'role-read',
            }
        },
        {
              path: 'roles/create',
              name: 'Role Create',
              component: RoleAdd,
              meta: {
                  title: 'POJ - Role Create',
                  requiresAuth: true,
                  permission : 'role-create',
              }
        },
        {
            path: 'roles/detail/:id',
            name: 'Role Detail',
            component: RoleDetail,
            meta: {
                title: 'POJ - Role Detail',
                requiresAuth: true,
                permission : 'role-read',
            }
        },
        {
            path: 'roles/edit/:id',
            name: 'Role Edit',
            component: RoleEdit,
            meta: {
                title: 'POJ - Role Edit',
                requiresAuth: true,
                permission : 'role-update',
            }
        },
        {
          path: 'permissions',
          name: 'Permissions Management',
          component: Permissions,
          meta: {
              title: 'POJ - Permissions Management',
              requiresAuth: true,
              permission : 'permission-read',
          }
        },
        {
          path: 'permissions/create',
          name: 'permission-create',
          component: PermissionAdd,
          meta: {
              title: 'POJ - Permission Create',
              requiresAuth: true,
              permission : 'permission-read',
          }
        },
        {
          path: 'permissions/detail/:id',
          name: 'Permission Detail',
          component: PermissionDetail,
          meta: {
              title: 'POJ - Permission Detail',
              requiresAuth: true,
              permission : 'permission-read',
          }
        },
        {
          path: 'permissions/edit/:id',
          name: 'Permission Edit',
          component: PermissionEdit,
          meta: {
              title: 'POJ - Permission Edit',
              requiresAuth: true,
              permission : 'permission-update'
          }
        },
        {
          path: 'employees',
          name: 'Employee Management',
          component: Employees,
          meta: {
              title: 'POJ - Employee Management',
              requiresAuth: true,
              permission : 'employee-read',
          }
        },
        {
          path: 'employees/detail/:id',
          name: 'employee_detail',
          component: EmployeeDetail,
          meta: {
              title: 'POJ - Employee Detail',
              requiresAuth: true,
              permission : 'employee-read',
          }
        },
          {
              path: '/management/department',
              name: 'department-list',
              component: Department,
              meta: {
                  title: 'POJ - Department List',
                  requiresAuth: true,
                  permission : 'department-read',
              }
          },
          {
              path: '/management/department/:id/:unit_id',
              name: 'department-edit',
              component: DepartmentEdit,
              meta: {
                  title: 'POJ - Department Edit',
                  requiresAuth: true,
                  permission : 'department-update',
              }
          },
          {
              path: '/management/teams',
              name: 'team-list',
              component: Team,
              meta: {
                  title: 'POJ - Team List',
                  requiresAuth: true,
                  permission : 'team-read',
              }
          },
          {
              path: '/management/job',
              name: 'job-list',
              component: Job,
              meta: {
                  title: 'POJ - Job List',
                  requiresAuth: true,
                  permission : 'job-read',
              }
          },
          {
              path: '/management/public-holiday',
              name: 'public-holiday-list',
              component: PublicHoliday,
              meta: {
                  title: 'POJ - Public Holiday',
                  requiresAuth: true,
                  permission : 'public-holiday-read',
              }
          },
          {
              path: '/management/job/edit/:id',
              name: 'job-edit',
              component: JobEdit,
              meta: {
                  title: 'POJ - Job Edit',
                  requiresAuth: true,
                  permission : 'job-update',
              }
          },
          {
              path: '/management/admin-unit',
              name: 'List Admin Unit',
              component: AdminUnit,
              meta: {
                  title: 'POJ - Admin Unit',
                  requiresAuth: true,
                  permission : 'admin-unit-read',
              }
          },
          {
              path: '/management/struktur-jabatan',
              name: 'unit-job',
              component: UnitJob,
              meta: {
                  title: 'POJ - Struktur Jabatan',
                  requiresAuth: true,
                  permission : 'job-assign-parent-create',
              }
          },
          {
              path: '/management/struktur-jabatan/canvas/:id',
              name: 'unit-job-canvas',
              component: JobCanvas,
              meta: {
                  title: 'POJ - Struktur Jabatan Canvas',
                  requiresAuth: true,
                  permission : 'job-assign-parent-create',
              }
          },
      ]
    },
    {
        path: '/general-settings',
        component: Body,
        children: [
            {
                path: '',
                name: 'General Settings',
                component: GeneralSettings,
                meta: {
                    title: 'POJ - General Settings',
                    requiresAuth: true,
                    permission : 'setting-read',
                },
            },
        ]
    },
    {
        path: '/syncs',
        component: Body,
        children: [
            {
                path: '',
                name: 'Syncronization',
                component: Syncs,
                meta: {
                    title: 'POJ - Syncronization',
                    requiresAuth: true,
                    permission : 'sync-read',
                },
            },
        ]
    },
    {
        path: '/cabang',
        component: Body,
        children: [
            {
                path: '',
                name: 'Cabang',
                component: Cabang,
                meta: {
                    title: 'POJ - Cabang',
                    requiresAuth: true,
                    permission: 'unit-read',
                },
            },
            {
                path: '/detail/:id',
                name: 'Cabang Detail',
                component: CabangDetail,
                meta: {
                    title: 'POJ - Cabang Detail',
                    requiresAuth: true,
                    permission: 'unit-read',
                }
            },
        ]
    },
    {
        path: '/timesheet-assignment',
        component: Body,
        children: [
            {
                path: '',
                name: 'Timesheet Assignment',
                component: TimesheetSchedule,
                meta: {
                    title: 'POJ - Timesheet Assignment',
                    requiresAuth: true,
                    permission: 'timesheet-read',
                },
            },
            {
                path: 'create',
                name: 'timesheet-schedule-create',
                component: TimesheetScheduleCreate,
                meta: {
                    title: 'POJ - Create Timesheet Assignment',
                    requiresAuth: true,
                    permission: 'timesheet-create',
                },
            },
            {
                path: 'edit/:id',
                name: 'timesheet-schedule-edit',
                component: TimesheetScheduleEdit,
                meta: {
                    title: 'POJ - Edit Timesheet Assignment',
                    requiresAuth: true,
                    permission: 'timesheet-update',
                },
            },
            {
                path: 'detail',
                name: 'timesheet-schedule-detail',
                component: TimesheetScheduleDetail,
                meta: {
                    title: 'POJ - Timesheet Assignment Detail',
                    requiresAuth: true,
                    permission: 'timesheet-read',
                },
            },
        ]
    },
    {
        path: '/approval',
        component: Body,
        children: [
            {
                path: '',
                name: 'Approvals',
                component: Approval,
                meta: {
                    title: 'POJ - Approvals',
                    requiresAuth: true,
                    permission: 'approval-read',
                },
            },
            {
                path: 'create',
                name: 'Create Approvals',
                component: CreateApproval,
                meta: {
                    title: 'POJ - Create Approvals',
                    requiresAuth: true,
                    permission: 'approval-create',
                },
            },
            {
                path: 'edit/:id',
                name: 'approval_edit',
                component: EditApproval,
                meta: {
                    title: 'POJ - Edit Approvals',
                    requiresAuth: true,
                    permission: 'approval-update',
                },
            },
            {
                path: 'details/:id',
                name: 'approval_details',
                component: DetailApproval,
                meta: {
                    title: 'POJ - Detail Approvals',
                    requiresAuth: true,
                    permission: 'approval-read',
                },
            },
        ]
    },
    {
        path: '/attendance',
        component: Body,
        children: [
            {
                path: '',
                name: 'Attendances',
                component: Attendances,
                meta: {
                    title: 'POJ - Attendances',
                    requiresAuth: true,
                    permission: 'attendance-read',
                },
            },
            {
                path: 'attendance-approval',
                name: 'Attendance Approval',
                component: AttendanceApproval,
                meta: {
                    title: 'POJ - Attendances Approval',
                    requiresAuth: true,
                    permission: 'attendance-approval-read',
                },
            },
            {
                path: 'detail-attendance/:id',
                name: 'Detail Attendance',
                component: DetailAttendance,
                meta: {
                    title: 'POJ - Detail Attendance',
                    requiresAuth: true,
                    permission: 'attendance-detail-read',
                },
            },
            {
                path: 'backup',
                name: 'Backup',
                component: Backup,
                meta: {
                    title: 'POJ - Backup',
                    requiresAuth: true,
                    permission: 'backup-request-read',
                },
            },
            {
                path: 'create-backup',
                name: 'Create Backup',
                component: CreateBackup,
                meta: {
                    title: 'POJ - Create Backup',
                    requiresAuth: true,
                    permission: 'backup-request-create',
                },
            },
            {
                path: 'detail-backup/:id',
                name: 'Detail Backup',
                component: DetailBackup,
                meta: {
                    title: 'POJ - Detail Backup',
                    requiresAuth: true,
                    permission: 'backup-detail-read',
                },
            },
            {
                path: 'employee-backup',
                name: 'Jadwal Backup',
                component: EmployeeBackup,
                meta: {
                    title: 'POJ - Jadwal Backup',
                    requiresAuth: true,
                    permission: 'backup-schedule-read',
                },
            },
            {
                path: 'approval-backup',
                name: 'Approval Backup',
                component: ListApprovalBackup,
                meta: {
                    title: 'POJ - Approval Backup',
                    requiresAuth: true,
                    permission: 'backup-approval-read',
                },
            },
            {
                path: 'overtime',
                name: 'Overtime',
                component: Overtime,
                meta: {
                    title: 'POJ - Overtime',
                    requiresAuth: true,
                    permission: 'overtime-request-read',
                },
            },
            {
                path: 'overtime/create',
                name: 'Create Overtime',
                component: CreateOvertime,
                meta: {
                    title: 'POJ - Create Overtime',
                    requiresAuth: true,
                    permission: 'overtime-request-create',
                },
            },
            {
                path: 'detail-overtime/:id',
                name: 'Detail Overtime',
                component: DetailOvertime,
                meta: {
                    title: 'POJ - Detail Overtime',
                    requiresAuth: true,
                    permission: 'overtime-detail-read',
                },
            },
            {
                path: 'employee-overtime',
                name: 'Jadwal Overtime',
                component: EmployeeOvertime,
                meta: {
                    title: 'POJ - Jadwal Overtime',
                    requiresAuth: true,
                    permission: 'overtime-schedule-read',
                },
            },
            {
                path: 'approval-overtime',
                name: 'Approval Overtime',
                component: ListApprovalOvertime,
                meta: {
                    title: 'POJ - Approval Overtime',
                    requiresAuth: true,
                    permission: 'overtime-approval-read',
                },
            }
        ]
    },
    {
        path: '/approval-module',
        component: Body,
        children: [
            {
                path: '',
                name: 'Approvals Module',
                component: ApprovalModule,
                meta: {
                    title: 'POJ - Approval Modules',
                    requiresAuth: true,
                    permission: 'approval-module-read',
                },
            },
        ]
    },
    {
        path: '/profile',
        component: Body,
        children: [
            {
                path: '',
                name: 'Profile',
                component: Profile,
                meta: {
                    title: 'POJ - Profile',
                    requiresAuth: true,
                    permission: 'profile-read',
                }
            }
        ]
    },

    {
        path: '/operating-unit',
        component: Body,
        children: [
            {
                path: '',
                name: 'operating-unit',
                component: Regional,
                meta: {
                    title: 'POJ - Operating Unit',
                    requiresAuth: true,
                    permission: 'unit-read',
                },
            },
            {
                path: '/regionals/detail/:id',
                name: 'regional_detail',
                component: RegionalDetail,
                meta: {
                    title: 'POJ - Regional Detail',
                    requiresAuth: true,
                    permission: 'unit-read',
                }
            },
        ]
    },
    {
        path: '/corporates',
        component: Body,
        children: [
            {
                path: '',
                name: 'Corporate',
                component: Corporates,
                meta: {
                    title: 'POJ - Corporate',
                    requiresAuth: true,
                    permission: 'unit-read',
                },
            },
            {
                path: '/corporates/detail/:id',
                name: 'Corporate Detail',
                component: CorporateDetail,
                meta: {
                    title: 'POJ - Corporate Detail',
                    requiresAuth: true,
                    permission: 'unit-read',
                }
            },
        ]
    },
    {
        path: '/kanwils',
        component: Body,
        children: [
            {
                path: '',
                name: 'Kanwil',
                component: Kanwils,
                meta: {
                    title: 'POJ - Kantor Wilayah',
                    requiresAuth: true,
                    permission: 'unit-read',
                },
            },
            {
                path: '/kanwils/detail/:id',
                name: 'Kanwil Detail',
                component: KanwilDetail,
                meta: {
                    title: 'POJ - Kantor Wilayah Detail',
                    requiresAuth: true,
                    permission: 'unit-read',
                }
            },
        ]
    },
    {
        path: '/areas',
        component: Body,
        children: [
            {
                path: '',
                name: 'Area',
                component: Areas,
                meta: {
                    title: 'POJ - Area',
                    requiresAuth: true,
                    permission: 'unit-read',
                },
            },
            {
                path: '/areas/detail/:id',
                name: 'Area Detail',
                component: AreaDetail,
                meta: {
                    title: 'POJ - Area Detail',
                    requiresAuth: true,
                    permission: 'unit-read',
                }
            },
        ]
    },
    {
        path: '/outlets',
        component: Body,
        children: [
            {
                path: '',
                name: 'Outlet',
                component: Outlets,
                meta: {
                    title: 'POJ - Outlet',
                    requiresAuth: true,
                    permission: 'unit-read',
                },
            },
            {
                path: '/outlets/detail/:id',
                name: 'Outlet Detail',
                component: OutletDetail,
                meta: {
                    title: 'POJ - Outlet Detail',
                    requiresAuth: true,
                    permission: 'unit-read',
                }
            },
        ]
    },
    {
        path: '/leave-request',
        component: Body,
        children: [
            {
                path: '',
                name: 'leave-request',
                component: LeaveRequest,
                meta: {
                    title: 'POJ - Leave Request',
                    requiresAuth: true,
                    permission: 'leave-request-read',
                },
            },
            {
                path: 'details/:id',
                name: 'leave-request-details',
                component: LeaveRequestDetail,
                meta: {
                    title: 'POJ - Leave Request Detail',
                    requiresAuth: true,
                    permission: 'leave-request-detail-read',
                },
            },
        ]
    },
    {
        path: '/leave-approval',
        component: Body,
        children: [
            {
                path: '',
                name: 'Leave Approval',
                component: LeaveApproval,
                meta: {
                    title: 'POJ - Leave Approval',
                    requiresAuth: true,
                    permission: 'leave-report-read',
                },
            },
        ]
    },
    {
        path: '/leave-master',
        component: Body,
        children: [
            {
                path: '',
                name: 'Leave Master',
                component: LeaveMaster,
                meta: {
                    title: 'POJ - Leave Master',
                    requiresAuth: true,
                    permission: 'master-leave-read',
                },
            }
        ]
    },
    {
        path: '/incident-reporting',
        component: Body,
        children: [
            {
                path: '',
                name: 'Incident Reporting',
                component: IncidentReporting,
                meta: {
                    title: 'POJ - Incident Reporting',
                    requiresAuth: true,
                    permission: 'incident-reporting-read',
                },
            },
            {
                path: 'detail/:id',
                name: 'incident_reporting_detail',
                component: IncidentReportingDetail,
                meta: {
                    title: 'POJ - Incident Reporting',
                    requiresAuth: true,
                    permission : 'incident-reporting-read',
                }
            },
        ]
    },
    {
        path: '/work-reporting',
        component: Body,
        children: [
            {
                path: '',
                name: 'Work Reporting',
                component: WorkReporting,
                meta: {
                    title: 'POJ - Work Reporting',
                    requiresAuth: true,
                    permission: 'work-reporting-read',
                },
            },
            {
                path: 'detail/:id',
                name: 'Work Reporting Detail',
                component: WorkReportingDetail,
                meta: {
                    title: 'POJ - Work Reporting Detail',
                    requiresAuth: true,
                    permission : 'work-reporting-read',
                }
            },
        ]
    },
    {
        path: '/timesheet-reporting',
        component: Body,
        children: [
            {
                path: '',
                name: 'Timesheet Reporting',
                component: TimesheetReportingIndex,
                meta: {
                    title: 'POJ - Timesheet Reporting',
                    requiresAuth: true,
                    permission: 'timesheet-reporting-read',
                },
            },
            {

                path: 'detail/:id',
                name: 'Detail Timesheet Reporting',
                component: TimesheetReportingDetail,
                meta: {
                    title: 'POJ - Detail Timesheet Reporting',
                    requiresAuth: true,
                    permission: 'timesheet-reporting-read',
                },
            },
            {
                path: 'create',
                name: 'Create Timesheet Reporting',
                component: TimesheetReportingCreate,
                meta: {
                    title: 'POJ - Create Timesheet Reporting',
                    requiresAuth: true,
                    permission: 'timesheet-reporting-create',
                },
            },
            {
                path: 'my',
                name: 'Timesheet Reporting Saya',
                component: EmployeeTimesheetReporting,
                meta: {
                    title: 'POJ - Timesheet Reporting Saya',
                    requiresAuth: true,
                    permission: 'timesheet-reporting-create',
                },
            },
        ]
    },
    {
        path: '/event',
        component: Body,
        children: [
            {
                path: '',
                name: 'event_request_list',
                component: EventRequestList,
                meta: {
                    title: 'POJ - Event Request',
                    requiresAuth: true,
                    permission: 'event-read',
                },
            },
            {
                path: 'detail/:id',
                name: 'event_request_detail',
                component: EventRequestDetail,
                meta: {
                    title: 'POJ - Event Request',
                    requiresAuth: true,
                    permission : 'event-detail-read',
                }
            },
            {
                path: 'create',
                name: 'event_request_create',
                component: EventRequestCreate,
                meta: {
                    title: 'POJ - Create Event Request',
                    requiresAuth: true,
                    permission : 'event-create',
                }
            },
            {
                path: 'employee-event',
                name: 'employee_event',
                component: EventEmployeeEvent,
                meta: {
                    title: 'POJ - Employee Event',
                    requiresAuth: true,
                    permission : 'event-employee-read',
                }
            },
            {
                path: 'event-approval',
                name: 'event_approval',
                component: EventApproval,
                meta: {
                    title: 'POJ - Employee Approval',
                    requiresAuth: true,
                    permission : 'event-approval-read',
                }
            },
        ]
    },
    {
        path: '/error',
        name: 'errorPage',
        component: Error,
        meta: {
            title: 'POJ - Error Page',
            requiresAuth: true,
            permission: 'dashboard',
        }
    }
]
const router=createRouter({
    history: createWebHistory(),
    routes,
})

const permissions = JSON.parse(localStorage.getItem('USER_PERMISSIONS'));

router.beforeEach((to, from, next) => {
    if (to.matched.some(record => record.meta.requiresAuth)) {
      if (!store.getters.isAuthenticated) {
        next('/auth/login');
      } else if (to.meta.requiresAuth && (!store.getters.isAuthenticated || !permissions.includes(to.meta.permission))) {
          next('/');
          useToast().error('You are not authorized to access this page', {
              duration: 5000,
              position: 'top-center',
              isClosable: true
          })

      } else {
        next();
      }

    }  else {
      next();
    }
});
export default router
