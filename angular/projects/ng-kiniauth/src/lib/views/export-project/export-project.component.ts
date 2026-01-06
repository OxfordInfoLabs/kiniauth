import {Component, OnInit} from '@angular/core';
import {ProjectService} from "../../services/project.service";
import * as lodash from 'lodash';
const _ = lodash.default;

@Component({
    selector: 'ka-export-project',
    templateUrl: './export-project.component.html',
    styleUrls: ['./export-project.component.sass'],
    standalone: false
})
export class ExportProjectComponent implements OnInit {

    public exportableResources: any;
    public exportConfig: any = {};
    public _ = _;

    constructor(private projectService: ProjectService) {
    }

    ngOnInit(): void {
        this.projectService.getExportableProjectResources().then((resources: any) => {
            this.exportableResources = resources.resourcesByType;

            const localStoredVersionRaw = localStorage.getItem(this.getLocalStorageKey());
            const localStoredVersion = localStoredVersionRaw ? JSON.parse(localStoredVersionRaw) : {};

            Object.keys(this.exportableResources).forEach((category) => {
                this.exportConfig[category] = {};
                const storedConfig = localStoredVersion[category] || {};
                this.exportableResources[category].forEach(resource => {
                    this.exportConfig[category][resource.identifier] = storedConfig[resource.identifier] || resource.defaultConfig;
                });
            });


        });
    }

    export(exportConfig: any = {}) {
        this.projectService.exportProject(this.exportConfig);
        localStorage.setItem(this.getLocalStorageKey(), JSON.stringify(this.exportConfig));
    }

    // Get the local storage key.
    private getLocalStorageKey() {
        const account: any = JSON.parse(sessionStorage.getItem('sessionData'));
        return 'export-' + account.account.accountId + '-' + this.projectService.activeProject.getValue().projectKey;
    }
}
