import {Injectable} from '@angular/core';
import {BehaviorSubject} from 'rxjs';
import {HttpClient} from '@angular/common/http';
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
}
