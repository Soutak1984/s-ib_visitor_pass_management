"use strict"

// //sidebar functionality

const sidebar = document.getElementById('sidebar')
const opensidebar = document.querySelector('.open-sidebar-button')
const closesidebar = document.getElementById('close-sidebar')
opensidebar.addEventListener('click', function (){
    sidebar.classList.add('sidebar-active')
})
closesidebar.addEventListener('click', function (){
    sidebar.classList.remove('sidebar-active')
})
// Language selector functionality

const dropdownbtn = document.querySelectorAll('.dropdownbtn')
dropdownbtn.forEach(btn=>{
    btn.addEventListener('click', function (){
       const dropdown = document.querySelectorAll('.dropdown')
       dropdown.forEach(btn=>{
        btn.classList.toggle('dropdown-active')
       })
        const icon = document.querySelectorAll('.dropdown-icon')
        icon.forEach(icon=>{
            icon.classList.toggle('rotate')
        })
    })
})
    document.querySelectorAll('.dropdown-content a').forEach(item => {
        item.addEventListener('click', function(event) {
            const selectedLang = this.getAttribute('data-lang');
            const currentlang = document.querySelectorAll('#current-lang');
            currentlang.forEach(lang =>{
                lang.textContent = selectedLang;
            })
            const dropdown = this.closest('.dropdown');
            dropdown.classList.remove('dropdown-active');
            dropdown.querySelector('.dropdown-content').classList.add('hidden');
            dropdown.querySelector('.dropdown-icon').classList.remove('rotate');
        });
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.dropdown')) {
            const dropdown =  document.querySelectorAll('.dropdown')
            dropdown.forEach(btn=>{
             btn.classList.remove('dropdown-active')
            })
            const icon = document.querySelectorAll('.dropdown-icon')
        icon.forEach(icon=>{
            icon.classList.remove('rotate')
        })
        }
    });
})