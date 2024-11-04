import {Injectable} from '@angular/core';
import {BehaviorSubject} from 'rxjs';
import {HttpClient, HttpHeaders} from '@angular/common/http';
import {AuthenticationService} from './authentication.service';
import {KiniAuthModuleConfig} from '../../ng-kiniauth.module';

@Injectable({
    providedIn: 'root'
})
export class ProjectService {

    public activeProject = new BehaviorSubject(null);

    constructor(private config: KiniAuthModuleConfig,
                private http: HttpClient,
                private authService: AuthenticationService) {

        const activeProject = localStorage.getItem('activeProject');
        if (activeProject) {
            this.setActiveProject(JSON.parse(activeProject));
        }
    }

    public getProjects(filterString = '') {
        return this.http.get(this.config.accessHttpURL + '/project', {
            params: {filterString}
        });
    }

    public getProject(key) {
        return this.http.get(this.config.accessHttpURL + '/project/' + key).toPromise();
    }

    public createProject(name, description) {
        return this.http.post(this.config.accessHttpURL + '/project', {
            name, description
        }).toPromise();
    }

    public removeProject(key) {
        return this.http.delete(this.config.accessHttpURL + '/project/' + key).toPromise();
    }

    public async updateProjectSettings(projectKey, settings) {
        await this.http.put(this.config.accessHttpURL + '/project/' + projectKey + '/settings', settings)
            .toPromise();
        const project = await this.getProject(projectKey);
        this.setActiveProject(project);
        return project;
    }

    public setActiveProject(project) {
        this.activeProject.next(project);
        localStorage.setItem('activeProject', JSON.stringify(project));
    }

    public resetActiveProject() {
        this.activeProject.next(null);
        localStorage.removeItem('activeProject');
    }

    public isActiveProjectAdmin() {
        const session = this.authService.sessionData.getValue();
        if (session && session.privileges) {
            const projectKey = this.activeProject.getValue() ? this.activeProject.getValue().projectKey : null;
            const privileges = session.privileges.PROJECT;

            if (privileges['*']) {
                return true;
            }

            return privileges[projectKey] ? privileges[projectKey].indexOf('*') > -1 : false;
        }
        return false;
    }

    public doesActiveProjectHavePrivilege(privilegeKey: string) {
        const session: any = this.authService.sessionData.getValue();
        if (session && session.privileges) {
            const projectKey = this.activeProject.getValue() ? this.activeProject.getValue().projectKey : null;
            const privileges = session.privileges.PROJECT;

            const scope = (privileges['*'] || privileges[projectKey]) || [];

            return scope.indexOf('*') > -1 || scope.indexOf(privilegeKey) > -1;
        }
        return false;
    }

    public canAccountManageProjects() {
        const session: any = this.authService.sessionData.getValue();
        if (session && session.privileges) {
            const projectKey = this.activeProject.getValue() ? this.activeProject.getValue().projectKey : null;
            const privileges = session.privileges.ACCOUNT;

            const scope = (privileges['*'] || privileges[projectKey]) || [];

            return scope.indexOf('*') > -1 || scope.indexOf('projectmanager') > -1;
        }
        return false;
    }

    public setDataItemPagingValue(limit, offset, page, itemName?) {
        const projectKey = this.activeProject.getValue() ? this.activeProject.getValue().projectKey : '';
        itemName = itemName || window.location.pathname + projectKey;
        sessionStorage.setItem(itemName + 'Limit', String(limit));
        sessionStorage.setItem(itemName + 'Offset', String(offset));
        sessionStorage.setItem(itemName + 'Page', String(page));
    }

    public getDataItemPagingValues(itemName?) {
        const projectKey = this.activeProject.getValue() ? this.activeProject.getValue().projectKey : '';
        itemName = itemName || window.location.pathname + projectKey;
        const values: any = {};

        const limitValue = sessionStorage.getItem(itemName + 'Limit');
        if (limitValue) {
            values.limit = Number(limitValue);
        }

        const offsetValue = sessionStorage.getItem(itemName + 'Offset');
        if (offsetValue) {
            values.offset = Number(offsetValue);
        }

        const pageValue = sessionStorage.getItem(itemName + 'Page');
        if (pageValue) {
            values.page = Number(pageValue);
        }

        return values;
    }

    /**
     * Get exportable project resources
     */
    public getExportableProjectResources() {
        const projectKey = this.activeProject.getValue().projectKey;
        return this.http.get(this.config.accessHttpURL + '/project/export/resources/' + projectKey).toPromise();
    }

    /**
     * Export a project based on export config
     *
     * @param exportConfig
     */
    public async exportProject(exportConfig: any) {
        const projectKey = this.activeProject.getValue().projectKey;
        const data = await this.http.post(this.config.accessHttpURL + '/project/export/' + projectKey, exportConfig,
            {headers: new HttpHeaders({'Content-Type': 'external'}), responseType: 'arraybuffer'}).toPromise();

        const a = document.createElement('a');
        const blob = new Blob([data], {type: 'application/octet-stream'});
        a.href = URL.createObjectURL(blob);
        a.download = projectKey + '-' + Date.now() + '.json';
        a.click();

    }


    /**
     * Analyse a project import for imported file data
     *
     * @param importFileData
     */
    public async analyseImport(importFileData) {
        const projectKey = this.activeProject.getValue().projectKey;
        const HttpUploadOptions = {
            headers: new HttpHeaders({'Content-Type': 'file'})
        };
        return this.http.post(this.config.accessHttpURL + '/project/import/analyse/' + projectKey,
            importFileData, HttpUploadOptions).toPromise();
    }


    public async import(importFileData){

        const projectKey = this.activeProject.getValue().projectKey;
        const HttpUploadOptions = {
            headers: new HttpHeaders({'Content-Type': 'file'})
        };
        return this.http.post(this.config.accessHttpURL + '/project/import/' + projectKey,
            importFileData, HttpUploadOptions).toPromise();

    }

}
