<template>
    <div id="sidebar-menu">

      <ul
          class="sidebar-links custom-scrollbar"
          id="myDIV"
          :style="[layoutobject.split(' ').includes('horizontal-wrapper')  ? layout.settings.layout_type==='rtl'? {'  -right': margin+'px'} : {'margin-left': margin+'px'} :  { margin : '0px'}]"
      >
        <li class="back-btn">
          <router-link to="/">
            <img
              class="img-fluid"
              src="../../assets/images/logo/logo-icon.png"
              alt=""
          /></router-link>
          <div class="mobile-back text-end">
            <span>Back</span>
              <i class="fa fa-angle-right ps-2" aria-hidden="true"></i>
          </div>
        </li>
        <li
          v-for="(menuItem, index) in menuItems"
          :key="index" class="sidebar-list"
          :class="{ ' sidebar-main-title': menuItem.type === 'headtitle'}"
          :hidden="!this.permissions.includes(menuItem.permission)"
        >

          <div v-if="menuItem.type === 'headtitle' && this.permissions.includes(menuItem.permission)">
            <h6 class="lan-1">{{ (menuItem.headTitle1) }}</h6>
          </div>

          <label :class="'badge badge-' + menuItem.badgeType" v-if="menuItem.badgeType">
            {{ (menuItem.badgeValue) }}
          </label>

          <a class="sidebar-link sidebar-title" href="javascript:void(0)" :class="{ 'active': menuItem.active }"
             v-if="menuItem.type === 'sub' && this.permissions.includes(menuItem.permission)" @click="setNavActive(menuItem, index)">
              <svg class="stroke-icon">
                  <use :href="`/assets/svg/icon-sprite.svg#${menuItem.icon}`"></use>
              </svg>
              <svg class="fill-icon">
                  <use :href="`/assets/svg/icon-sprite.svg#${menuItem.iconf}`"></use>
              </svg>
              <vue-feather :type="menuItem.icon"></vue-feather>
            <span class="lan-3">
              {{ (menuItem.title) }}
            </span>
            <div class="according-menu" v-if="menuItem.children">
              <i class="fa fa-angle-right pull-right"></i>
            </div>
          </a>
          <router-link :to="menuItem.path" class="sidebar-link sidebar-title" v-if="menuItem.type === 'link' && hasPermission(menuItems)"
            :class="{ 'active': menuItem.active }" v-on:click="hidesecondmenu()"
            @click="setNavActive(menuItem, index)">
            <svg class="stroke-icon">
                <use :href="`/assets/svg/icon-sprite.svg#${menuItem.icon}`"></use>
            </svg>
            <svg class="fill-icon">
                <use :href="`/assets/svg/icon-sprite.svg#${menuItem.iconf}`"></use>
            </svg>
            <vue-feather :type="menuItem.icon"></vue-feather>
            <span>
                {{ (menuItem.title) }}
            </span>
            <i class="fa fa-angle-right pull-right" v-if="menuItem.children"></i>
          </router-link>

          <a :href="menuItem.path" class="sidebar-link sidebar-title" v-if="menuItem.type === 'extLink' && hasPermission(menuItems)"
            @click="setNavActive(menuItem, index)">
            <svg class="stroke-icon">
                <use :xlink:href="iconSprite + `#${menuItem.icon}`"></use>
            </svg>
            <svg class="fill-icon">
                <use :xlink:href="iconSprite + `#${menuItem.iconf}`"></use>
            </svg>
            <span>
                {{ (menuItem.title) }}
            </span>
            <i class="fa fa-angle-right pull-right" v-if="menuItem.children"></i>
          </a>

          <a :href="menuItem.path" target="_blank" class="sidebar-link sidebar-title"
             v-if="menuItem.type === 'extTabLink' && hasPermission(menuItems)" @click="setNavActive(menuItem, index)">
            <svg class="stroke-icon">
                <use :xlink:href="require('@/assets/svg/icon-sprite.svg') + `#${menuItem.icon}`"></use>
            </svg>
            <svg class="fill-icon">
                <use :xlink:href="require('@/assets/svg/icon-sprite.svg') + `#${menuItem.iconf}`"></use>
            </svg>
            <span>
                {{ (menuItem.title) }}
            </span>
            <i class="fa fa-angle-right pull-right" v-if="menuItem.children"></i>
          </a>

          <ul class="sidebar-submenu" v-if="menuItem.children && hasPermission(menuItems)" :class="{ 'menu-open': menuItem.active }"
            :style="{ display: menuItem.active ? '' : 'none' }">

            <li v-for="(childrenItem, index) in menuItem.children" :key="index">

                <a class="lan-4" :class="{ 'active': childrenItem.active }" href="javascript:void(0)"
                   v-if="childrenItem.type === 'sub' && permissions.includes(childrenItem.permission)" @click="setNavActive(childrenItem, index)">
                    {{ (childrenItem.title) }}
                    <label :class="'badge badge-' + childrenItem.badgeType + ' pull-right'"
                        v-if="childrenItem.badgeType">{{ childrenItem.badgeValue }}</label>
                    <i class="fa pull-right mt-1"
                        v-bind:class="[childrenItem.active ? 'fa fa-angle-down' : 'fa fa-angle-right']"
                        v-if="childrenItem.children"></i>
                </a>

                <router-link class="lan-4" :class="{ 'active': childrenItem.active }" :to="childrenItem.path"
                             v-if="childrenItem.type === 'link' && (!Array.isArray(permissions) || permissions.includes(childrenItem.permission)) && this.checkUnitLevel(childrenItem.title)" @click="setNavActive(childrenItem, index)"
                             v-on:click="hidesecondmenu()">
                    {{ (childrenItem.title) }}
                    <label :class="'badge badge-' + childrenItem.badgeType + ' pull-right'"
                        v-if="childrenItem.badgeType">{{ (childrenItem.badgeValue) }}</label>
                    <i class="fa fa-angle-right pull-right mt-1" v-if="childrenItem.children"></i>
                </router-link>

                <a :href="childrenItem.path" v-if="childrenItem.type == 'extLink' && permissions.includes(childrenItem.permission)" class="submenu-title">
                    {{ (childrenItem.title) }}
                    <label :class="'badge badge-' + childrenItem.badgeType + ' pull-right'"
                        v-if="childrenItem.badgeType">{{ (childrenItem.badgeValue) }}</label>
                    <i class="fa fa-angle-right pull-right mt-1" v-if="childrenItem.children"></i>
                </a>

                <a class="submenu-title" :href="childrenItem.path && permissions.includes(childrenItem.permission)" target="_blank"
                    v-if="childrenItem.type == 'extTabLink'">
                    {{ (childrenItem.title) }}
                    <label :class="'badge badge-' + childrenItem.badgeType + ' pull-right'"
                        v-if="childrenItem.badgeType">{{ (childrenItem.badgeValue) }}</label>
                    <i class="fa fa-angle-right pull-right mt-1" v-if="childrenItem.children"></i>
                </a>

                <ul class="nav-sub-childmenu submenu-content" v-if="childrenItem.children && permissions.includes(childrenItem.permission)"
                    :class="{ 'opensubchild': childrenItem.active }">
                    <li v-for="(childrenSubItem, index) in childrenItem.children" :key="index">

                        <router-link :class="{ 'active': childrenSubItem.active }" :to="childrenSubItem.path"
                            v-if="childrenSubItem.type == 'link' && permissions.includes(childrenSubItem.permission)" v-on:click="hidesecondmenu()"
                            @click="setNavActive(childrenSubItem, index)">
                            {{ (childrenSubItem.title) }}
                            <label :class="'badge badge-' + childrenSubItem.badgeType + ' pull-right'"
                                v-if="childrenSubItem.badgeType">{{ (childrenSubItem.badgeValue) }}</label>
                            <i class="fa fa-angle-right pull-right" v-if="childrenSubItem.children"></i>
                        </router-link>

                        <router-link :to="childrenSubItem.path" v-if="childrenSubItem.type == 'extLink' && permissions.includes(childrenSubItem.permission)">
                            {{ (childrenSubItem.title) }}
                            <label :class="'badge badge-' + childrenSubItem.badgeType + ' pull-right'"
                                v-if="childrenSubItem.badgeType">{{ (childrenSubItem.badgeValue) }}</label>
                            <i class="fa fa-angle-right pull-right" v-if="childrenSubItem.children"></i>
                        </router-link>

                        <router-link :to="childrenSubItem.path" v-if="childrenSubItem.type == 'extLink' && permissions.includes(childrenSubItem.permission)">
                            {{ (childrenSubItem.title) }}
                            <label :class="'badge badge-' + childrenSubItem.badgeType + ' pull-right'"
                                v-if="childrenSubItem.badgeType">{{ (childrenSubItem.badgeValue) }}</label>
                            <i class="fa fa-angle-right pull-right" v-if="childrenSubItem.children"></i>
                        </router-link>
                    </li>
                </ul>
            </li>
          </ul>
        </li>

      </ul>
    </div>
</template>
<script>
import {mapState} from 'vuex';
import {layoutClasses} from '@/constants/layout';

export default {
name: 'Navmenu',
data() {
  return {
    layoutobj:{},
    items: [],
    text: '',
    active: false,
    permissions: [],
      activeUser: null,
      listUnitLevel: {
          'Operating Unit': 2,
          'Corporate': 3,
          'Kantor Wilayah': 4,
          'Area': 5,
          'Cabang': 6,
          'Outlet': 7
      }
  };
},
computed: {
  ...mapState({
    menuItems: state => state.menu.data,
    layout: state => state.layout.layout,
    sidebar: state => state.layout.sidebarType,
    activeoverlay: (state) => state.menu.activeoverlay,
    togglesidebar: (state) => state.menu.togglesidebar,
    width: (state) => state.menu.width,
    height: (state) => state.menu.height,
    margin: (state) => state.menu.margin,
    menuWidth: (state) => state.menu.menuWidth,
    activeUser: (state) => state.user
  }),
  layoutobject: {
    get: function () {
      return JSON.parse(JSON.stringify(layoutClasses.find((item) => Object.keys(item).pop() === this.layout.settings.layout)))[this.layout.settings.layout];
    },
    set: function () {
      this.layoutobj = layoutClasses.find((item) => Object.keys(item).pop() === this.layout.settings.layout);
      this.layoutobj = JSON.parse(JSON.stringify(this.layoutobj))[this.layout.settings.layout];
      return this.layoutobj;
    }
  },
},
watch: {
  width() {
    window.addEventListener('resize', this.handleResize);
    this.handleResize();
    window.addEventListener('scroll',this.handleScroll);
    this.handleScroll();
    if (window.innerWidth < 992) {
      const newlayout = JSON.parse(JSON.stringify(this.layoutobject).replace('horizontal-wrapper', 'compact-wrapper'));
      document.querySelector('.page-wrapper').className = 'page-wrapper ' + newlayout;
      this.$store.state.menu.margin = 0;
    } else {
      document.querySelector('.page-wrapper').className = 'page-wrapper ' + this.layoutobject;
    }

    if((window.innerWidth < 1199 && this.layout.settings.layout === 'Tokyo') || (window.innerWidth < 1199 && this.layout.settings.layout === 'Moscow') || (window.innerWidth < 1199 && this.layout.settings.layout === 'Rome')) {
      this.menuItems.filter(menuItem => {
        menuItem.active = false;
      });
    }
  }
},
created() {
  window.addEventListener('resize', this.handleResize);
  this.handleResize();
  if (this.$store.state.menu.width < 991) {
    this.layout.settings.layout = 'Dubai';
    this.margin = 0;
  }
  setTimeout(()=> {
    const elmnt = document.getElementById('myDIV');
    this.$store.state.menu.menuWidth = elmnt.offsetWidth;
    this.$store.state.menu.menuWidth > window.innerWidth  ?
      (this.$store.state.menu.hideRightArrow = false, this.$store.state.menu.hideLeftArrowRTL = false) :
      (this.$store.state.menu.hideRightArrow = false, this.$store.state.menu.hideLeftArrowRTL = true);
  }, 500);
  this.layoutobject = layoutClasses.find((item) => Object.keys(item).pop() === this.layout.settings.layout);
  this.layoutobject = JSON.parse(JSON.stringify(this.layoutobject))[this.layout.settings.layout];
},
destroyed() {
  window.removeEventListener('resize', this.handleResize);
},
mounted() {
    this.hasPermission();
    this.getPermission();
    this.getActiveUser();
    this.menuItems.filter(items => {
    if (items.path === this.$route.path)
      this.$store.dispatch('menu/setActiveRoute', items);
    if (!items.children) return false;
    items.children.filter(subItems => {
      if (subItems.path === this.$route.path)
        this.$store.dispatch('menu/setActiveRoute', subItems);
      if (!subItems.children) return false;
      subItems.children.filter(subSubItems => {
        if (subSubItems.path === this.$route.path)
          this.$store.dispatch('menu/setActiveRoute', subSubItems);
      });
    });
  });
    this.checkUnitLevel()
},
methods: {
    hasPermission(menuItem) {
        const requiredPermission = menuItem?.permission;
        const ls = JSON.parse(localStorage.getItem('USER_ROLES'));

        if (ls.length > 0) {
            return true;
        }

        if (ls.some(role => role === 'superadmin')) {
            return true;
        }

        if (requiredPermission) {
            return this.$store.state.permissions.includes(requiredPermission);
        }

        return false;
    },
    getPermission() {
        const getPermission = localStorage.getItem('USER_PERMISSIONS');
        this.permissions = JSON.parse(getPermission);
        return this.permissions
    },
    getActiveUser() {
        this.activeUser = JSON.parse(localStorage.getItem('USER_STORAGE_KEY'));
    },
  handleScroll() {
    if(window.scrollY > 400){
      if(this.layoutobject.split(' ').pop() === 'material-type' || this.layoutobject.split(' ').pop() ==='advance-layout')
        document.querySelector('.sidebar-main').className = 'sidebar-main hovered';
    }else{
      if(document.getElementById('sidebar-main'))
        document.querySelector('.sidebar-main').className = 'sidebar-main';
    }
  },
  setNavActive(item) {
    this.$store.dispatch('menu/setNavActive', item);
    if(this.layoutobject.split(' ').includes('compact-sidebar') && window.innerWidth > 991) {
      this.$store.state.menu.activeoverlay = !!this.menuItems.some(menuItem => menuItem.active === true);
    }
  },
  hidesecondmenu() {
    if(window.innerWidth < 991) {
      this.$store.state.menu.activeoverlay = false;
      this.$store.state.menu.togglesidebar = false;
      this.menuItems.filter(menuItem => {
        menuItem.active = false;
      });
    } else if(this.layoutobject.split(' ').includes('compact-sidebar')){
      this.$store.state.menu.activeoverlay = false;
      this.menuItems.filter(menuItem => {
        menuItem.active = false;
      });
    }
  },
  handleResize() {
    this.$store.state.menu.width = window.innerWidth - 450;
  },
    checkUnitLevel(title) {
        let levelUnit = this.activeUser?.last_units?.unit_level
        let availableMenu = ['Operating Unit', 'Corporate', 'Kantor Wilayah', 'Area', 'Cabang', 'Outlet']
        let unitLevelMatchRole = ['staff', 'staff_approvals']
	    let unitLevelRole = ["admin_operating_unit"]

        if (levelUnit && availableMenu.includes(title) && unitLevelMatchRole.includes(this.$store.state.currentRole)) {
            let validationData ={
                'title': title,
                'titleLevel': this.listUnitLevel[title],
                'levelUnit': levelUnit,
            }
            return validationData.titleLevel === validationData.levelUnit
        } else if (levelUnit && availableMenu.includes(title) && unitLevelRole.includes(this.$store.state.currentRole))  {
			let activeUnit = this.$store.state.activeAdminUnit?.unit_relation_id ?? '';

			if (activeUnit) {
				let levelUnitAdmin = activeUnit.split("-")
				let validationData ={
					'title': title,
					'titleLevel': this.listUnitLevel[title],
					'levelUnit': levelUnitAdmin[1],
				}
				return validationData.titleLevel >= validationData.levelUnit
			}
        }

        return true
    }
}
};
</script>
