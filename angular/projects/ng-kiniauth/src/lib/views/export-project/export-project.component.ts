import {Component, OnInit} from '@angular/core';
import {ProjectService} from "../../services/project.service";
import {merge, Subject} from "rxjs";
import {debounceTime, switchMap} from "rxjs/operators";

@Component({
    selector: 'ka-export-project',
    templateUrl: './export-project.component.html',
    styleUrls: ['./export-project.component.sass']
})
export class ExportProjectComponent implements OnInit {

    public projectSub = new Subject();
    public exportableResources: any;
    public notificationGroups: any = {};

    constructor(private projectService: ProjectService) {
    }

    ngOnInit(): void {
        if (this.projectService) {
            this.projectSub = this.projectService.activeProject;
        }

        this.projectService.getExportableProjectResources().then((resources) => {
            this.exportableResources = resources;
        });
    }

    export(exportConfig: any = {}) {
        exportConfig.includedNotificationGroupIds = [];
        Object.keys(this.notificationGroups).forEach(key => {
            if (this.notificationGroups[key])
                exportConfig.includedNotificationGroupIds.push(key);
        });
        this.projectService.exportProject(exportConfig);

    }
}
